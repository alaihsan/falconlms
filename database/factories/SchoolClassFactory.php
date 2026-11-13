<?php

namespace Database\Factories;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Kelas '.fake()->randomElement(['X', 'XI', 'XII']).' '.fake()->randomElement(['IPA 1', 'IPA 2', 'IPS 1']),
            'subject' => fake()->randomElement(['Matematika', 'Bahasa Indonesia', 'Fisika', 'Informatika']),
            'room' => 'Ruang '.fake()->numberBetween(101, 210),
            'code' => strtoupper(Str::random(6)),
            'description' => fake()->sentence(),
            'guru_id' => User::factory()->guru(),
        ];
    }
}
