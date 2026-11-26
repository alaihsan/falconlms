<?php

use App\Enums\UserRole;
use App\Models\AssignmentSubmission;
use App\Models\LearningItem;
use App\Models\SchoolClass;
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
    $this->assertSoftDeleted($target);
});

test('deleting a guru keeps their classes, content, and student grades', function () {
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();
    $class = SchoolClass::factory()->create(['guru_id' => $guru->id]);
    $assignment = LearningItem::factory()->hasAttached($class, relationship: 'classes')->create([
        'user_id' => $guru->id,
        'type' => 'assignment',
    ]);
    $submission = AssignmentSubmission::factory()->create([
        'learning_item_id' => $assignment->id,
        'score' => 80,
        'graded_at' => now(),
    ]);

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $guru));

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success');
    $this->assertSoftDeleted($guru);
    $this->assertModelExists($class);
    $this->assertModelExists($assignment);
    $this->assertModelExists($submission);
});

test('admin cannot change their own role', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => 'guru',
    ]);

    $response->assertSessionHasErrors(['role' => 'Anda tidak dapat mengubah peran akun Anda sendiri.']);
    expect($admin->refresh()->role)->toBe(UserRole::Admin);
});

test('a guru who still teaches a class cannot be changed into a siswa', function () {
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();
    SchoolClass::factory()->create(['guru_id' => $guru->id]);

    $response = $this->actingAs($admin)->put(route('admin.users.update', $guru), [
        'name' => $guru->name,
        'email' => $guru->email,
        'role' => 'siswa',
    ]);

    $response->assertSessionHasErrors([
        'role' => 'Pengguna ini masih mengajar 1 kelas. Hapus kelasnya terlebih dahulu sebelum mengubah peran menjadi Siswa.',
    ]);
    expect($guru->refresh()->role)->toBe(UserRole::Guru);
});

test('a guru without classes can be changed into a siswa', function () {
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();

    $response = $this->actingAs($admin)->put(route('admin.users.update', $guru), [
        'name' => $guru->name,
        'email' => $guru->email,
        'role' => 'siswa',
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHasNoErrors();
    expect($guru->refresh()->role)->toBe(UserRole::Siswa);
});

test('admin cannot set a password shorter than 8 characters', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Ibu Sri Mulyani',
        'email' => 'sri@falcon.sch.id',
        'role' => 'guru',
        'password' => 'pass123',
    ]);

    $response->assertSessionHasErrors(['password' => 'Kata sandi minimal 8 karakter.']);
    $this->assertDatabaseMissing('users', ['email' => 'sri@falcon.sch.id']);
});
