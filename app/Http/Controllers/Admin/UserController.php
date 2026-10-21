<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportStudentsRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()?->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * Import students from uploaded CSV file.
     */
    public function importCsv(ImportStudentsRequest $request): RedirectResponse
    {
        $file = $request->file('file');

        if (! $file || ! $file->isValid()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Berkas CSV tidak valid.');
        }

        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Gagal membuka berkas CSV.');
        }

        $imported = 0;
        $skipped = 0;
        $rowNumber = 0;
        $firstRow = true;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rowNumber++;

                // Skip completely empty rows
                if (empty(array_filter($row, static fn (?string $v): bool => $v !== null && trim($v) !== ''))) {
                    continue;
                }

                // Header row check
                if ($firstRow) {
                    $firstRow = false;
                    $firstCol = strtolower(trim($row[0] ?? ''));
                    if (in_array($firstCol, ['nama', 'name', 'nama lengkap'], true)) {
                        continue;
                    }
                }

                $name = trim($row[0] ?? '');
                $email = strtolower(trim($row[1] ?? ''));
                $username = isset($row[2]) ? trim($row[2]) : null;
                $password = isset($row[3]) && trim($row[3]) !== '' ? trim($row[3]) : 'siswa123';

                if ($name === '' || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;

                    continue;
                }

                // Check for duplicate email or username
                $exists = User::query()
                    ->where('email', $email)
                    ->when($username, fn (Builder $q) => $q->orWhere('username', $username))
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                User::create([
                    'name' => $name,
                    'email' => $email,
                    'username' => $username ?: null,
                    'role' => UserRole::Siswa,
                    'password' => Hash::make($password),
                    'must_change_password' => true,
                ]);

                $imported++;
            }

            fclose($handle);
            DB::commit();
        } catch (\Throwable $e) {
            fclose($handle);
            DB::rollBack();

            return redirect()->route('admin.users.index')
                ->with('error', 'Terjadi kesalahan saat mengimpor data: '.$e->getMessage());
        }

        $message = "Berhasil mengimpor {$imported} siswa.";
        if ($skipped > 0) {
            $message .= " {$skipped} baris dilewati karena duplikasi atau data tidak lengkap.";
        }

        return redirect()->route('admin.users.index')
            ->with('success', $message);
    }

    /**
     * Download template CSV file for student import.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_siswa.csv"',
        ];

        return response()->stream(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['nama', 'email', 'username', 'password']);
            fputcsv($handle, ['Ahmad Fauzi', 'fauzi@falcon.sch.id', 'siswa01', 'siswa123']);
            fputcsv($handle, ['Siti Nurhaliza', 'siti@falcon.sch.id', 'siswa02', 'siswa123']);
            fclose($handle);
        }, 200, $headers);
    }
}
