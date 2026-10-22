<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('an account can be created with a temporary password', function () {
    $this->artisan('falcon:create-user', [
        'username' => 'Guru01',
        'name' => 'Ibu Sari',
        '--role' => 'teacher',
    ])->assertSuccessful();

    $user = User::where('username', 'guru01')->sole();

    expect($user->name)->toBe('Ibu Sari')
        ->and($user->role)->toBe(Role::Teacher)
        ->and($user->must_change_password)->toBeTrue();
});

test('an account is not created with a taken username or unknown role', function () {
    User::factory()->create(['username' => 'guru01']);

    $this->artisan('falcon:create-user', ['username' => 'guru01', 'name' => 'Ibu Sari'])
        ->assertFailed();

    $this->artisan('falcon:create-user', ['username' => 'guru02', 'name' => 'Ibu Sari', '--role' => 'principal'])
        ->assertFailed();

    expect(User::count())->toBe(1);
});

test('a password can be reset to a temporary one', function () {
    $user = User::factory()->create(['username' => '0012345678']);

    $this->artisan('falcon:reset-password', ['username' => '0012345678'])->assertSuccessful();

    $user->refresh();

    expect($user->must_change_password)->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeFalse();
});

test('resetting an unknown username fails', function () {
    $this->artisan('falcon:reset-password', ['username' => 'nobody'])->assertFailed();
});
