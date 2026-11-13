<?php

namespace App\Http\Requests;

use App\Models\LearningItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VotePollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $learningItem = $this->route('learningItem');

        return $this->user() !== null
            && $learningItem instanceof LearningItem
            && $learningItem->isVisibleTo($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var LearningItem $learningItem */
        $learningItem = $this->route('learningItem');

        return [
            'poll_option_id' => [
                'required',
                'integer',
                Rule::exists('poll_options', 'id')->where('learning_item_id', $learningItem->id),
            ],
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
            'poll_option_id.required' => 'Pilih salah satu jawaban.',
            'poll_option_id.exists' => 'Pilihan jawaban tidak ditemukan pada polling ini.',
        ];
    }
}
