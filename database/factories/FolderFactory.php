<?php

namespace Database\Factories;

use App\Models\Folder;
use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'space_id' => Space::factory(),
            'name' => fake()->randomElement(['Bab 1', 'Bab 2', 'Latihan', 'Video', 'LKS']),
        ];
    }

    public function in(Folder $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'space_id' => $parent->space_id,
            'parent_id' => $parent->id,
            'path' => $parent->descendantPath(),
        ]);
    }
}
