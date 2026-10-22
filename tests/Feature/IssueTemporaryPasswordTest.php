<?php

use App\Actions\Users\IssueTemporaryPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('temporary passwords are easy to read aloud and must be changed', function () {
    $user = User::factory()->create();

    $password = app(IssueTemporaryPassword::class)->handle($user);

    $user->refresh();

    expect($password)->toMatch('/^[a-hj-km-np-z]{4}-[2-9]{4}$/')
        ->and(Hash::check($password, $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBeTrue();
});
