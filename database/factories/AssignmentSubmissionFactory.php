<?php

namespace Database\Factories;

use App\Models\AssignmentSubmission;
use App\Models\LearningItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentSubmission>
 */
class AssignmentSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'learning_item_id' => LearningItem::factory()->graded(),
            'siswa_id' => User::factory()->siswa(),
            'answer_text' => fake()->paragraph(),
            'score' => null,
            'feedback' => null,
            'submitted_at' => now(),
            'graded_at' => null,
        ];
    }
}
