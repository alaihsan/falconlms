<?php

use App\Models\Item;
use App\Models\Space;
use App\Models\User;
use App\Support\Uploads;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->teacher = User::factory()->teacher()->create();
    $this->student = User::factory()->create();
    $this->course = Space::factory()->withTeacher($this->teacher)->withStudents([$this->student])->create();
    $this->item = Item::factory()->create(['space_id' => $this->course->id]);
});

test('class members can open files of published posts', function () {
    $attachment = Uploads::attach($this->item, UploadedFile::fake()->create('foto.png', 10, 'image/png'), $this->teacher);

    $this->actingAs($this->student)
        ->get(route('attachments.show', $attachment))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('outsiders can not open class files', function () {
    $attachment = Uploads::attach($this->item, UploadedFile::fake()->create('foto.png', 10, 'image/png'), $this->teacher);

    $this->actingAs(User::factory()->create())->get(route('attachments.show', $attachment))->assertForbidden();
    $this->actingAs(User::factory()->teacher()->create())->get(route('attachments.show', $attachment))->assertForbidden();
});

test('files of drafts and deleted posts are not served to students', function () {
    $attachment = Uploads::attach($this->item, UploadedFile::fake()->create('foto.png', 10, 'image/png'), $this->teacher);

    $this->item->forceFill(['published_at' => null])->save();
    $this->actingAs($this->student)->get(route('attachments.show', $attachment))->assertForbidden();

    $this->item->forceFill(['published_at' => now()])->save();
    $this->item->delete();
    $this->actingAs($this->student)->get(route('attachments.show', $attachment))->assertForbidden();
});

test('only safe types are shown inline; the rest is downloaded inside a sandbox', function () {
    $pdf = Uploads::attach($this->item, UploadedFile::fake()->create('modul.pdf', 10, 'application/pdf'), $this->teacher);
    $zip = Uploads::attach($this->item, UploadedFile::fake()->create('tugas.zip', 10, 'application/zip'), $this->teacher);

    $pdfResponse = $this->actingAs($this->student)->get(route('attachments.show', $pdf));
    expect($pdfResponse->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($pdfResponse->headers->get('Content-Security-Policy'))->toBeNull();

    $zipResponse = $this->actingAs($this->student)->get(route('attachments.show', $zip));
    expect($zipResponse->headers->get('Content-Disposition'))->toStartWith('attachment')
        ->and($zipResponse->headers->get('Content-Type'))->toBe('application/octet-stream')
        ->and($zipResponse->headers->get('Content-Security-Policy'))->toContain('sandbox');
});

test('nginx can send the file when X-Accel-Redirect is configured', function () {
    config(['falcon.x_accel_redirect' => '/_protected']);
    $attachment = Uploads::attach($this->item, UploadedFile::fake()->create('foto.png', 10, 'image/png'), $this->teacher);

    $this->actingAs($this->student)
        ->get(route('attachments.show', $attachment))
        ->assertHeader('X-Accel-Redirect', '/_protected/'.$attachment->path);
});
