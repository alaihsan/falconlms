<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('users with a temporary password are sent to the change password page', function () {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('user-password.edit'));
});

test('the change password page tells whether the change is required', function () {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)
        ->get(route('user-password.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/ChangePassword')
            ->where('mustChangePassword', true),
        );
});

test('a temporary password can be replaced without typing it again', function () {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $user->refresh();

    expect(Hash::check('rahasia123', $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBeFalse();

    $this->actingAs($user)->get(route('home'))->assertOk();
});

test('a voluntary change requires the current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('user-password.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('user-password.edit'));

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check('rahasia123', $user->refresh()->password))->toBeTrue();
});

test('the new password can not be the username', function () {
    $user = User::factory()->mustChangePassword()->create(['username' => '0012345678']);

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'password' => '0012345678',
            'password_confirmation' => '0012345678',
        ])
        ->assertSessionHasErrors('password');
});

test('the new password can not be the current password', function () {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasErrors('password');

    expect($user->refresh()->must_change_password)->toBeTrue();
});
