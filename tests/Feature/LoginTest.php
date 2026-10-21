<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
});

test('users can authenticate using email', function () {
    $user = User::factory()->guru()->create([
        'email' => 'guru@falcon.sch.id',
        'password' => 'secret123',
    ]);

    $response = $this->post(route('login.store'), [
        'login' => 'guru@falcon.sch.id',
        'password' => 'secret123',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard'));
});

test('users can authenticate using username', function () {
    $user = User::factory()->siswa()->create([
        'username' => 'siswa_budi',
        'password' => 'secret123',
    ]);

    $response = $this->post(route('login.store'), [
        'login' => 'siswa_budi',
        'password' => 'secret123',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard'));
});

test('users cannot authenticate with invalid password', function () {
    $user = User::factory()->create([
        'email' => 'user@falcon.sch.id',
        'password' => 'secret123',
    ]);

    $response = $this->from(route('login'))->post(route('login.store'), [
        'login' => 'user@falcon.sch.id',
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('login');
});

test('users are rate limited after 5 failed attempts', function () {
    $user = User::factory()->create([
        'email' => 'target@falcon.sch.id',
        'password' => 'secret123',
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login.store'), [
            'login' => 'target@falcon.sch.id',
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post(route('login.store'), [
        'login' => 'target@falcon.sch.id',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('login');
    expect(session('errors')->first('login'))->toContain('seconds');
});

test('student with must_change_password flag is redirected to password change upon login', function () {
    $student = User::factory()->siswa()->mustChangePassword()->create([
        'email' => 'siswa_baru@falcon.sch.id',
        'password' => 'initial123',
    ]);

    $response = $this->post(route('login.store'), [
        'login' => 'siswa_baru@falcon.sch.id',
        'password' => 'initial123',
    ]);

    $this->assertAuthenticatedAs($student);
    $response->assertRedirect(route('password.change'));
});

test('user with must_change_password flag cannot access dashboard', function () {
    $student = User::factory()->siswa()->mustChangePassword()->create();

    $response = $this->actingAs($student)->get(route('dashboard'));

    $response->assertRedirect(route('password.change'));
});

test('authenticated users are redirected when accessing login page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('login'));

    $response->assertRedirect(route('dashboard'));
});

test('users can log out', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});
