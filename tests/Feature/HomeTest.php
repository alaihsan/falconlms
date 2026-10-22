<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('teachers get the library entries in the new menu', function () {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('context.space.kind', 'personal_library')
            ->where('context.allowed', ['folder', 'upload', 'upload_folder', 'material', 'assignment', 'quiz', 'link']),
        );
});

test('students get no new menu on the home page', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('context.allowed', []),
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
