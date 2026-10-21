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
        'Duplicate User,existing@falcon.sch.id,dup01,pass123',
        'Valid Student,valid@falcon.sch.id,valid01,pass123',
        'Invalid Email,invalid-email-format,inv01,pass123',
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
