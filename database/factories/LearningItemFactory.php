<?php

namespace Database\Factories;

use App\Models\LearningItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningItem>
 */
class LearningItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['announcement', 'material', 'assignment', 'quiz']);

        return [
            'user_id' => User::factory()->guru(),
            'type' => $type,
            'title' => fake()->sentence(4),
            'topic' => 'Bab 1: Pengenalan',
            'content' => fake()->paragraph(),
            'attachment_name' => null,
            'attachment_path' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
            'due_at' => now()->addDays(7),
            'max_score' => 100,
            'is_graded' => in_array($type, LearningItem::GRADED_BY_DEFAULT_TYPES, true),
        ];
    }

    /**
     * Indicate that the item is an assignment that enters the gradebook.
     */
    public function graded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'assignment',
            'is_graded' => true,
        ]);
    }
}
