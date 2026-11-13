<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesShareTargets;
use Illuminate\Foundation\Http\FormRequest;

class MoveLearningItemRequest extends FormRequest
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
            'folder_id' => ['present', 'nullable', 'integer', $this->ownedFolderRule()],
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
            'folder_id.exists' => 'Folder tujuan tidak ditemukan.',
        ];
    }
}
