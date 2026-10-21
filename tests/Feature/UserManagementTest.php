<?php

use App\Models\User;

test('only admin can access user management', function () {
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();
    $siswa = User::factory()->siswa()->create();

    // Guest gets redirected to login
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));

    // Guru gets 403 forbidden
    $this->actingAs($guru)->get(route('admin.users.index'))->assertForbidden();

    // Siswa gets 403 forbidden
    $this->actingAs($siswa)->get(route('admin.users.index'))->assertForbidden();

    // Admin can access
    $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
});

test('admin can search and filter users', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->guru()->create(['name' => 'Pak Joko Widodo', 'email' => 'joko@falcon.sch.id']);
    User::factory()->siswa()->create(['name' => 'Siti Aisyah', 'email' => 'siti@falcon.sch.id']);

    $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Joko']));
    $response->assertOk();

    $responseRole = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'guru']));
    $responseRole->assertOk();
});

test('admin can create new guru and siswa', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Ibu Sri Mulyani',
        'email' => 'sri@falcon.sch.id',
        'username' => 'sri_guru',
        'role' => 'guru',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'email' => 'sri@falcon.sch.id',
        'username' => 'sri_guru',
        'role' => 'guru',
    ]);
});

test('admin can update user details', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->guru()->create([
        'name' => 'Nama Lama',
        'email' => 'lama@falcon.sch.id',
    ]);

    $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => 'Nama Baru',
        'email' => 'baru@falcon.sch.id',
        'role' => 'guru',
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $user->refresh();
    expect($user->name)->toBe('Nama Baru');
    expect($user->email)->toBe('baru@falcon.sch.id');
});

test('admin cannot delete their own account', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('admin can delete another user', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->siswa()->create();

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $target));

    $response->assertRedirect(route('admin.users.index'));
    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});
