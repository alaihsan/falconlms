<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\ImportUsers;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserImportController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('admin/users/Import', [
            'breadcrumbs' => [
                ['title' => __('Users'), 'href' => route('admin.users.index')],
                ['title' => __('Import'), 'href' => route('admin.users.import')],
            ],
            'roles' => collect(Role::cases())->map(fn (Role $role) => ['value' => $role->value, 'label' => $role->label()]),
        ]);
    }

    /**
     * The result, including the temporary passwords, is rendered once and
     * never stored, so it can only be printed right now.
     */
    public function store(Request $request, ImportUsers $importUsers): Response
    {
        Gate::authorize('create', User::class);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:2048', 'extensions:csv,txt', 'mimes:csv,txt'],
            'default_role' => ['required', Rule::enum(Role::class)],
        ]);

        $result = $importUsers->handle((string) file_get_contents($data['file']->getRealPath()), Role::from($data['default_role']));

        return Inertia::render('admin/users/ImportResult', [
            'breadcrumbs' => [
                ['title' => __('Users'), 'href' => route('admin.users.index')],
                ['title' => __('Import result'), 'href' => route('admin.users.import')],
            ],
            'created' => $result['created'],
            'errors' => $result['errors'],
        ]);
    }
}
