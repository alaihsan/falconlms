<?php

namespace App\Http\Requests\Auth;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordUpdateRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * Users replacing a temporary password just typed it at login, so they
     * are not asked for it again.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        return [
            'current_password' => $user->must_change_password ? [] : $this->currentPasswordRules(),
            'password' => [
                ...$this->passwordRules(),
                function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    if (Str::lower((string) $value) === $user->username) {
                        $fail(__('The new password must be different from your username.'));
                    } elseif (Hash::check((string) $value, $user->password)) {
                        $fail(__('The new password must be different from your current password.'));
                    }
                },
            ],
        ];
    }
}
