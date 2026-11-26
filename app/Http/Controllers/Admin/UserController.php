<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportStudentsRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\StudentCsvImportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /**
     * Display a listing of users with search and filter.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $role = $request->string('role')->trim()->value();

        $users = User::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', function (Builder $query) use ($role): void {
                $query->where('role', $role);
            })
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'must_change_password' => $user->must_change_password,
                'created_at' => $user->created_at?->format('d/m/Y H:i'),
            ]);

        $roles = array_map(
            static fn (UserRole $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ],
            UserRole::cases(),
        );

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = UserRole::from($validated['role']);
        $mustChange = $request->has('must_change_password')
            ? $request->boolean('must_change_password')
            : ($role === UserRole::Siswa);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => ! empty($validated['username']) ? $validated['username'] : null,
            'role' => $role,
            'password' => Hash::make($validated['password']),
            'must_change_password' => $mustChange,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => ! empty($validated['username']) ? $validated['username'] : null,
            'role' => UserRole::from($validated['role']),
            'must_change_password' => $request->boolean('must_change_password'),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Soft-delete the specified user: the account can no longer sign in, but its classes, content, and grades stay.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()?->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna \"{$user->name}\" dihapus. Kelas, konten, dan nilai miliknya tetap tersimpan.");
    }

    /**
     * Import students from uploaded CSV file.
     */
    public function importCsv(ImportStudentsRequest $request, StudentCsvImportService $importService): RedirectResponse
    {
        $file = $request->file('file');

        if (! $file) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Berkas CSV tidak ditemukan.');
        }

        $result = $importService->import($file);

        return redirect()->route('admin.users.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Download template CSV file for student import.
     */
    public function downloadTemplate(StudentCsvImportService $importService): StreamedResponse
    {
        return $importService->downloadTemplate();
    }
}
