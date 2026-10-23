<?php

namespace Database\Factories;

use App\Models\Space;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'space_id' => Space::factory(),
            'name' => 'Bab '.fake()->unique()->numberBetween(1, 999),
            'position' => 0,
        ];
    }
}
