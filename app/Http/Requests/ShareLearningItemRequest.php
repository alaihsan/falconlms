<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesShareTargets;
use Illuminate\Foundation\Http\FormRequest;

class ShareLearningItemRequest extends FormRequest
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
            'class_ids' => ['present', 'array'],
            'class_ids.*' => ['integer', 'distinct', $this->shareableClassRule()],
            'teacher_ids' => ['present', 'array'],
            'teacher_ids.*' => ['integer', 'distinct', $this->shareableTeacherRule()],
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
            'class_ids.*.exists' => 'Anda hanya dapat membagikan ke kelas yang Anda ajar.',
            'teacher_ids.*.exists' => 'Konten hanya dapat dibagikan kepada guru lain.',
        ];
    }
}
