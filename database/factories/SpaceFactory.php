<?php

namespace Database\Factories;

use App\Enums\MemberRole;
use App\Enums\SpaceKind;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Space>
 */
class SpaceFactory extends Factory
{
    /**
     * Define the model's default state: a class.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => SpaceKind::Course,
            'name' => fake()->randomElement(['Matematika', 'Biologi', 'Sejarah', 'Bahasa Indonesia']).' '.fake()->randomElement(['X IPA 1', 'XI IPS 2', 'XII']),
            'section' => null,
            'subject' => null,
            'join_code' => fn () => Space::generateJoinCode(),
        ];
    }

    /**
     * A teacher's personal library.
     */
    public function personalLibrary(User $owner): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => SpaceKind::PersonalLibrary,
            'owner_id' => $owner->id,
            'name' => 'Pustaka Saya',
            'join_code' => null,
        ]);
    }

    /**
     * Add a teacher to the class.
     */
    public function withTeacher(User $teacher): static
    {
        return $this->state(fn (array $attributes) => ['owner_id' => $teacher->id])
            ->afterCreating(fn (Space $space) => $space->users()->attach($teacher, ['role' => MemberRole::Teacher]));
    }

    /**
     * Add students to the class.
     *
     * @param  iterable<User>  $students
     */
    public function withStudents(iterable $students): static
    {
        return $this->afterCreating(function (Space $space) use ($students) {
            foreach ($students as $student) {
                $space->users()->attach($student, ['role' => MemberRole::Student]);
            }
        });
    }
}
