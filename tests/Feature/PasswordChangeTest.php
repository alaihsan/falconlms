<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password change page can be rendered for authenticated user', function () {
    $student = User::factory()->siswa()->mustChangePassword()->create();

    $response = $this->actingAs($student)->get(route('password.change'));

    $response->assertStatus(200);
});

test('guest cannot access password change page', function () {
    $response = $this->get(route('password.change'));

    $response->assertRedirect(route('login'));
});

test('user can update password and clear must_change_password flag', function () {
    $student = User::factory()->siswa()->mustChangePassword()->create([
        'password' => 'oldpassword123',
    ]);

    $response = $this->actingAs($student)->post(route('password.update'), [
        'current_password' => 'oldpassword123',
        'password' => 'newpassword456',
        'password_confirmation' => 'newpassword456',
    ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success');

    $student->refresh();
    expect($student->must_change_password)->toBeFalse();
    expect(Hash::check('newpassword456', $student->password))->toBeTrue();

    // Now student can access dashboard directly
    $this->actingAs($student)->get(route('dashboard'))->assertStatus(200);
});

test('password update requires correct current password', function () {
    $student = User::factory()->siswa()->mustChangePassword()->create([
        'password' => 'oldpassword123',
    ]);

    $response = $this->actingAs($student)->from(route('password.change'))->post(route('password.update'), [
        'current_password' => 'wrongpassword',
        'password' => 'newpassword456',
        'password_confirmation' => 'newpassword456',
    ]);

    $response->assertRedirect(route('password.change'));
    $response->assertSessionHasErrors('current_password');

    $student->refresh();
    expect($student->must_change_password)->toBeTrue();
});

test('password update requires minimum length and confirmation', function () {
    $student = User::factory()->siswa()->mustChangePassword()->create([
        'password' => 'oldpassword123',
    ]);

    $response = $this->actingAs($student)->from(route('password.change'))->post(route('password.update'), [
        'current_password' => 'oldpassword123',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]);

    $response->assertRedirect(route('password.change'));
    $response->assertSessionHasErrors(['password']);
});
