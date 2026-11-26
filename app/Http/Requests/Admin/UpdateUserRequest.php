<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User $targetUser */
        $targetUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($targetUser->id)],
            'username' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($targetUser->id)],
            'role' => ['required', new Enum(UserRole::class)],
            'password' => ['nullable', 'string', Password::defaults()],
            'must_change_password' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Refuse role changes that would lock the admin out or leave classes taught by a student.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('role')) {
                    return;
                }

                /** @var User $targetUser */
                $targetUser = $this->route('user');
                $newRole = $this->enum('role', UserRole::class);

                if ($newRole === null || $newRole === $targetUser->role) {
                    return;
                }

                if ($targetUser->is($this->user())) {
                    $validator->errors()->add('role', 'Anda tidak dapat mengubah peran akun Anda sendiri.');

                    return;
                }

                if ($targetUser->isGuru() && $newRole === UserRole::Siswa) {
                    $taughtClassCount = $targetUser->teachingClasses()->count();

                    if ($taughtClassCount > 0) {
                        $validator->errors()->add(
                            'role',
                            "Pengguna ini masih mengajar {$taughtClassCount} kelas. Hapus kelasnya terlebih dahulu sebelum mengubah peran menjadi Siswa.",
                        );
                    }
                }
            },
        ];
    }

    /**
     * Custom Indonesian messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'username.unique' => 'Username sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda strip, dan garis bawah.',
            'role.required' => 'Peran pengguna wajib dipilih.',
            'password.min' => 'Kata sandi minimal :min karakter.',
        ];
    }
}
