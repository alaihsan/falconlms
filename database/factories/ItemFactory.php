<?php

namespace Database\Factories;

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Space;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state: a published material.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'space_id' => Space::factory(),
            'type' => ItemType::Material,
            'title' => fake()->sentence(3),
            'body' => '<p>'.fake()->sentence().'</p>',
            'published_at' => now(),
        ];
    }

    public function assignment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ItemType::Assignment,
            'due_at' => now()->addWeek(),
            'max_points' => 100,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['published_at' => null]);
    }

    public function inTopic(Topic $topic): static
    {
        return $this->state(fn (array $attributes) => [
            'space_id' => $topic->space_id,
            'topic_id' => $topic->id,
        ]);
    }
}
