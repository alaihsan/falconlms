<?php

namespace App\Http\Requests;

use App\Models\AssignmentSubmission;
use Illuminate\Foundation\Http\FormRequest;

class GradeSubmissionRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $maxScore = $this->maxScore();

        return [
            'score' => ['required', 'integer', 'min:0', ...($maxScore !== null ? ["max:{$maxScore}"] : [])],
            'feedback' => ['nullable', 'string', 'max:1000'],
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
            'score.required' => 'Nilai wajib diisi.',
            'score.integer' => 'Nilai harus berupa angka bulat.',
            'score.min' => 'Nilai tidak boleh kurang dari 0.',
            'score.max' => 'Nilai untuk tugas ini maksimal :max.',
        ];
    }

    /**
     * Get the highest score the graded item allows, or null when the item no longer exists.
     *
     * A missing item is refused by the controller, so validation does not need a limit for it.
     */
    private function maxScore(): ?int
    {
        $submission = $this->route('submission');

        return $submission instanceof AssignmentSubmission
            ? $submission->learningItem?->max_score
            : null;
    }
}
