<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\IssueTemporaryPassword;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CreationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * User management for administrators. Every route sits behind password
 * confirmation (see routes/web.php).
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(Role::class)],
        ]);

        $users = User::query()
            ->when($filters['q'] ?? null, fn ($query, string $search) => $query->where(
                fn ($query) => $query->whereLike('name', "%{$search}%")->orWhereLike('username', "%{$search}%"),
            ))
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role->value,
                'roleLabel' => $user->role->label(),
                'mustChangePassword' => $user->must_change_password,
            ]);

        return Inertia::render('admin/users/Index', [
            'context' => CreationContext::forAdminUsers()->toArray(),
            'breadcrumbs' => [['title' => __('Users'), 'href' => route('admin.users.index')]],
            'users' => $users,
            'filters' => ['q' => $filters['q'] ?? '', 'role' => $filters['role'] ?? ''],
            'roles' => collect(Role::cases())->map(fn (Role $role) => ['value' => $role->value, 'label' => $role->label()]),
        ]);
    }

    public function store(Request $request, IssueTemporaryPassword $issueTemporaryPassword): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $request->merge(['username' => Str::lower(trim((string) $request->input('username')))]);

        $data = $request->validate([
            'username' => ['required', 'string', 'regex:/^[a-z0-9._-]{1,50}$/', Rule::unique(User::class)],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        $user = (new User)->forceFill(['username' => $data['username'], 'name' => $data['name'], 'role' => Role::from($data['role'])]);
        $password = $issueTemporaryPassword->handle($user);

        Inertia::flash('temporaryPassword', ['username' => $user->username, 'name' => $user->name, 'password' => $password]);

        return back();
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        $user->fill(['name' => $data['name']]);

        if (Role::from($data['role']) !== $user->role) {
            Gate::authorize('changeRole', $user);
            $user->forceFill(['role' => Role::from($data['role'])]);
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Saved.')]);

        return back();
    }

    public function resetPassword(User $user, IssueTemporaryPassword $issueTemporaryPassword): RedirectResponse
    {
        Gate::authorize('update', $user);

        $password = $issueTemporaryPassword->handle($user);

        Inertia::flash('temporaryPassword', ['username' => $user->username, 'name' => $user->name, 'password' => $password]);

        return back();
    }
}
