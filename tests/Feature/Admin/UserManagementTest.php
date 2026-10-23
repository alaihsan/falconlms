<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

function asConfirmedAdmin(User $admin)
{
    return test()->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()]);
}

test('only admins reach user management, after confirming their password', function () {
    $this->actingAs(User::factory()->teacher()->create())->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('admin.users.index'))->assertRedirect(route('password.confirm'));
    asConfirmedAdmin($this->admin)->get(route('admin.users.index'))->assertOk();
});

test('admins can search users by name or username', function () {
    User::factory()->create(['name' => 'Budi Santoso', 'username' => '0012345678']);
    User::factory()->create(['name' => 'Siti Aminah']);

    asConfirmedAdmin($this->admin)->get(route('admin.users.index', ['q' => 'budi']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/Index')
            ->count('users.data', 1)
            ->where('users.data.0.username', '0012345678'),
        );
});

test('a new account gets a temporary password', function () {
    asConfirmedAdmin($this->admin)->post(route('admin.users.store'), ['username' => 'Guru01', 'name' => 'Ibu Sari', 'role' => 'teacher'])
        ->assertSessionHasNoErrors();

    $user = User::query()->where('username', 'guru01')->sole();

    expect($user->role)->toBe(Role::Teacher)->and($user->must_change_password)->toBeTrue();
});

test('admins reset passwords but can not change their own role', function () {
    $student = User::factory()->create();

    asConfirmedAdmin($this->admin)->post(route('admin.users.reset-password', $student));
    expect($student->refresh()->must_change_password)->toBeTrue();

    asConfirmedAdmin($this->admin)->patch(route('admin.users.update', $this->admin), ['name' => 'Admin', 'role' => 'student'])
        ->assertForbidden();
    expect($this->admin->refresh()->role)->toBe(Role::Admin);

    asConfirmedAdmin($this->admin)->patch(route('admin.users.update', $student), ['name' => 'Siswa Baru', 'role' => 'teacher']);
    expect($student->refresh()->role)->toBe(Role::Teacher);
});

test('accounts are imported from an Excel CSV with semicolons and Indonesian headers', function () {
    User::factory()->create(['username' => 'sudahada']);
    $csv = "\xEF\xBB\xBFNIS;Nama;Peran\n0011;Budi Santoso;siswa\n0012;Siti Aminah;\nguru7;Pak Joko;guru\nsudahada;Dobel;siswa\n0011;Kembar;siswa\nbad name;X;siswa\n0013;Y;kepala\n";

    asConfirmedAdmin($this->admin)->post(route('admin.users.import.store'), [
        'file' => UploadedFile::fake()->createWithContent('siswa.csv', $csv),
        'default_role' => 'student',
    ])->assertInertia(fn (Assert $page) => $page
        ->component('admin/users/ImportResult')
        ->count('created', 3)
        ->count('errors', 4)
        ->where('created.0.username', '0011')
        ->where('errors.0.line', 5),
    );

    expect(User::query()->where('username', 'guru7')->value('role'))->toBe(Role::Teacher)
        ->and(User::query()->where('username', '0012')->value('must_change_password'))->toBeTrue();
});
