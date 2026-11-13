<?php

namespace Database\Factories;

use App\Models\LearningItem;
use App\Models\PollOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PollOption>
 */
class PollOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'learning_item_id' => LearningItem::factory()->state(['type' => 'poll', 'is_graded' => false]),
            'label' => fake()->words(3, true),
            'position' => 0,
        ];
    }
}
