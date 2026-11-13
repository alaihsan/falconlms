<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradebookRequest extends FormRequest
{
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
            'scores' => ['required', 'array', 'max:5000'],
            'scores.*.learning_item_id' => ['required', 'integer'],
            'scores.*.siswa_id' => ['required', 'integer'],
            'scores.*.score' => ['present', 'nullable', 'integer', 'min:0', 'max:1000'],
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
            'scores.required' => 'Tidak ada perubahan nilai untuk disimpan.',
            'scores.*.score.integer' => 'Nilai harus berupa angka bulat.',
            'scores.*.score.min' => 'Nilai tidak boleh kurang dari 0.',
        ];
    }
}
