<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('only admin can import students via csv', function () {
    $guru = User::factory()->guru()->create();
    $csv = UploadedFile::fake()->create('students.csv', 100, 'text/csv');

    $this->actingAs($guru)->post(route('admin.users.import'), [
        'file' => $csv,
    ])->assertForbidden();
});

test('admin can download csv template', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.users.template'));

    $response->assertOk();
    $response->assertHeader('Content-Disposition', 'attachment; filename="template_siswa.csv"');
});

test('admin can import students from valid csv file', function () {
    $admin = User::factory()->admin()->create();

    $csvContent = implode("\n", [
        'nama,email,username,password',
        'Budi Pratama,budi.p@falcon.sch.id,budi01,secret123',
        'Dewi Sartika,dewi.s@falcon.sch.id,dewi02,secret123',
    ]);

    $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

    $response = $this->actingAs($admin)->post(route('admin.users.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success');

    $budi = User::where('email', 'budi.p@falcon.sch.id')->first();
    expect($budi)->not->toBeNull();
    expect($budi->name)->toBe('Budi Pratama');
    expect($budi->username)->toBe('budi01');
    expect($budi->role)->toBe(UserRole::Siswa);
    expect($budi->must_change_password)->toBeTrue();

    $dewi = User::where('email', 'dewi.s@falcon.sch.id')->first();
    expect($dewi)->not->toBeNull();
    expect($dewi->role)->toBe(UserRole::Siswa);
    expect($dewi->must_change_password)->toBeTrue();
});

test('csv import skips duplicate emails and malformed rows', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'existing@falcon.sch.id']);

    $csvContent = implode("\n", [
        'nama,email,username,password',
        'Duplicate User,existing@falcon.sch.id,dup01,pass1234',
        'Valid Student,valid@falcon.sch.id,valid01,pass1234',
        'Invalid Email,invalid-email-format,inv01,pass1234',
    ]);

    $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

    $response = $this->actingAs($admin)->post(route('admin.users.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success');

    expect(User::where('email', 'valid@falcon.sch.id')->exists())->toBeTrue();
    expect(User::where('username', 'dup01')->exists())->toBeFalse();
    expect(User::where('username', 'inv01')->exists())->toBeFalse();
});

test('csv rows without a password or with a short password are skipped instead of getting a default password', function () {
    $admin = User::factory()->admin()->create();
    $csvContent = implode("\n", [
        'nama,email,username,password',
        'Tanpa Sandi,tanpa@falcon.sch.id,tanpa01,',
        'Sandi Pendek,pendek@falcon.sch.id,pendek01,abc1234',
        'Sandi Cukup,cukup@falcon.sch.id,cukup01,abcd1234',
    ]);
    $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

    $response = $this->actingAs($admin)->post(route('admin.users.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas(
        'success',
        'Berhasil mengimpor 1 siswa. 2 baris dilewati karena duplikasi, data tidak lengkap, atau kata sandi kurang dari 8 karakter.',
    );
    $this->assertDatabaseMissing('users', ['email' => 'tanpa@falcon.sch.id']);
    $this->assertDatabaseMissing('users', ['email' => 'pendek@falcon.sch.id']);
    $this->assertDatabaseHas('users', ['email' => 'cukup@falcon.sch.id']);
});

test('csv import skips a student whose deleted account still holds the email', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->siswa()->create(['email' => 'lama@falcon.sch.id'])->delete();
    $csvContent = implode("\n", [
        'nama,email,username,password',
        'Siswa Lama,lama@falcon.sch.id,lama01,abcd1234',
    ]);
    $file = UploadedFile::fake()->createWithContent('students.csv', $csvContent);

    $response = $this->actingAs($admin)->post(route('admin.users.import'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success');
    expect(User::withTrashed()->where('email', 'lama@falcon.sch.id')->count())->toBe(1);
    $this->assertDatabaseMissing('users', ['username' => 'lama01']);
});
