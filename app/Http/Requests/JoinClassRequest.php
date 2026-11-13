<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isSiswa() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20'],
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
            'code.required' => 'Kode kelas wajib diisi.',
        ];
    }
}
