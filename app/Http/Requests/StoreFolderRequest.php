<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesShareTargets;
use Illuminate\Foundation\Http\FormRequest;

class StoreFolderRequest extends FormRequest
{
    use ValidatesShareTargets;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isGuru() || $user->isAdmin());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer', $this->ownedFolderRule()],
        ];
    }

    /**
     * Custom Indonesian validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama folder wajib diisi.',
            'parent_id.exists' => 'Folder induk tidak ditemukan.',
        ];
    }
}
