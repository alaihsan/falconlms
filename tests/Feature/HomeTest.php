<?php

use App\Models\Item;
use App\Models\Space;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('teachers see their classes and recent library items on the home page', function () {
    $teacher = User::factory()->teacher()->create();
    $course = Space::factory()->withTeacher($teacher)->create(['name' => 'Biologi XI']);
    Item::factory()->create(['space_id' => Space::personalLibraryFor($teacher)->id, 'title' => 'Sel hewan']);

    $this->actingAs($teacher)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('courses.0.id', $course->id)
            ->where('courses.0.role', 'teacher')
            ->where('recent.0.title', 'Sel hewan'),
        );
});

test('only safe user fields are shared with the browser', function () {
    $user = User::factory()->teacher()->create(['email' => 'guru@sekolah.lan']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => 'teacher',
                'role_label' => 'Guru',
            ]),
        );
});
