<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

test('login screen can be rendered', function () {
    $this->get(route('login'))->assertOk();
});

test('guests are redirected to the login screen', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('users can log in with their username', function () {
    $user = User::factory()->create(['username' => '0012345678']);

    $response = $this->post(route('login.store'), [
        'username' => '0012345678',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('home', absolute: false));
});

test('usernames are not case sensitive', function () {
    $user = User::factory()->create(['username' => 'BudiSantoso']);

    expect($user->username)->toBe('budisantoso');

    $this->post(route('login.store'), [
        'username' => 'BUDISANTOSO',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

test('users can not log in with an invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('users can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect('/');

    $this->assertGuest();
});

test('login attempts are rate limited per username', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->username, '127.0.0.1'])), amount: 5);

    $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'wrong-password',
    ])->assertTooManyRequests();
});

test('registration and password reset by email are not available', function () {
    expect(Route::has('register'))->toBeFalse()
        ->and(Route::has('password.request'))->toBeFalse()
        ->and(Route::has('verification.notice'))->toBeFalse();
});

test('the confirm password screen can be rendered', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('password.confirm'))
        ->assertOk();
});
