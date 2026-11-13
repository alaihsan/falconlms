<?php

use App\Models\Folder;
use App\Models\LearningItem;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

test('guru can upload a file into a folder and share it to several classes', function () {
    Storage::fake(LearningItem::ATTACHMENT_DISK);
    $guru = User::factory()->guru()->create();
    $folder = Folder::factory()->create(['user_id' => $guru->id]);
    $classes = SchoolClass::factory()->count(2)->create(['guru_id' => $guru->id]);

    $response = $this->actingAs($guru)->post(route('library.store'), [
        'type' => 'material',
        'title' => 'Modul Jaringan',
        'folder_id' => $folder->id,
        'class_ids' => $classes->modelKeys(),
        'attachment' => UploadedFile::fake()->create('modul-jaringan.pdf', 120, 'application/pdf'),
    ]);

    $response->assertRedirect()->assertSessionHas('success');

    $item = LearningItem::where('title', 'Modul Jaringan')->sole();
    expect($item->folder_id)->toBe($folder->id)
        ->and($item->attachment_name)->toBe('modul-jaringan.pdf')
        ->and($item->classes()->pluck('school_classes.id')->all())->toEqualCanonicalizing($classes->modelKeys());
    Storage::disk(LearningItem::ATTACHMENT_DISK)->assertExists($item->attachment_path);
});

test('upload rejects unsupported file types', function () {
    Storage::fake(LearningItem::ATTACHMENT_DISK);
    $guru = User::factory()->guru()->create();

    $response = $this->actingAs($guru)->post(route('library.store'), [
        'type' => 'material',
        'title' => 'Skrip Berbahaya',
        'attachment' => UploadedFile::fake()->create('skrip.php', 5, 'text/x-php'),
    ]);

    $response->assertSessionHasErrors('attachment');
    $this->assertDatabaseMissing('learning_items', ['title' => 'Skrip Berbahaya']);
});

test('guru cannot share an item to a class taught by another guru', function () {
    $guru = User::factory()->guru()->create();
    $otherClass = SchoolClass::factory()->create();

    $response = $this->actingAs($guru)->post(route('library.store'), [
        'type' => 'material',
        'title' => 'Materi Titipan',
        'class_ids' => [$otherClass->id],
    ]);

    $response->assertSessionHasErrors('class_ids.0');
    $this->assertDatabaseMissing('learning_items', ['title' => 'Materi Titipan']);
});

test('guru cannot store an item in another guru folder', function () {
    $guru = User::factory()->guru()->create();
    $foreignFolder = Folder::factory()->create();

    $response = $this->actingAs($guru)->post(route('library.store'), [
        'type' => 'material',
        'title' => 'Materi Nyasar',
        'folder_id' => $foreignFolder->id,
    ]);

    $response->assertSessionHasErrors('folder_id');
});

test('owner can update the classes and teachers an item is shared with', function () {
    $guru = User::factory()->guru()->create();
    $colleague = User::factory()->guru()->create();
    [$keptClass, $removedClass, $addedClass] = SchoolClass::factory()->count(3)->create(['guru_id' => $guru->id]);
    $item = LearningItem::factory()
        ->hasAttached([$keptClass, $removedClass], relationship: 'classes')
        ->create(['user_id' => $guru->id]);

    $response = $this->actingAs($guru)->put(route('library.share', $item), [
        'class_ids' => [$keptClass->id, $addedClass->id],
        'teacher_ids' => [$colleague->id],
    ]);

    $response->assertRedirect()->assertSessionHas('success');
    expect($item->classes()->pluck('school_classes.id')->all())
        ->toEqualCanonicalizing([$keptClass->id, $addedClass->id])
        ->and($item->sharedWith()->pluck('users.id')->all())->toBe([$colleague->id]);
});

test('a guru the item was shared with cannot change its sharing', function () {
    $colleague = User::factory()->guru()->create();
    $item = LearningItem::factory()->hasAttached($colleague, relationship: 'sharedWith')->create();

    $response = $this->actingAs($colleague)->put(route('library.share', $item), [
        'class_ids' => [],
        'teacher_ids' => [],
    ]);

    $response->assertForbidden();
    expect($item->sharedWith()->count())->toBe(1);
});

test('items cannot be shared with students', function () {
    $guru = User::factory()->guru()->create();
    $siswa = User::factory()->siswa()->create();
    $item = LearningItem::factory()->create(['user_id' => $guru->id]);

    $response = $this->actingAs($guru)->put(route('library.share', $item), [
        'class_ids' => [],
        'teacher_ids' => [$siswa->id],
    ]);

    $response->assertSessionHasErrors('teacher_ids.0');
});

test('shared view lists items from colleagues but not unshared ones', function () {
    $guru = User::factory()->guru()->create();
    $shared = LearningItem::factory()->hasAttached($guru, relationship: 'sharedWith')->create();
    $private = LearningItem::factory()->create();

    $response = $this->actingAs($guru)->get(route('library.index', ['view' => 'shared']));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Library/Index')
        ->where('view', 'shared')
        ->has('items', 1)
        ->where('items.0.id', $shared->id)
        ->where('items.0.can_manage', false));
});

test('siswa only sees items shared to a class they are enrolled in', function () {
    $siswa = User::factory()->siswa()->create();
    $enrolledClass = SchoolClass::factory()->create();
    $enrolledClass->students()->attach($siswa->id);
    $visible = LearningItem::factory()->hasAttached($enrolledClass, relationship: 'classes')->create();
    LearningItem::factory()->hasAttached(SchoolClass::factory(), relationship: 'classes')->create();
    LearningItem::factory()->create();

    $response = $this->actingAs($siswa)->get(route('library.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('items', 1)
        ->where('items.0.id', $visible->id));
});

test('my library shows only the contents of the opened folder', function () {
    $guru = User::factory()->guru()->create();
    $folder = Folder::factory()->create(['user_id' => $guru->id]);
    $subfolder = Folder::factory()->create(['user_id' => $guru->id, 'parent_id' => $folder->id]);
    $inside = LearningItem::factory()->create(['user_id' => $guru->id, 'folder_id' => $folder->id]);
    LearningItem::factory()->create(['user_id' => $guru->id]);

    $response = $this->actingAs($guru)->get(route('library.index', ['folder' => $folder->id]));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('items', 1)
        ->where('items.0.id', $inside->id)
        ->has('folders', 1)
        ->where('folders.0.id', $subfolder->id)
        ->where('breadcrumbs.0.id', $folder->id));
});

test('guru cannot open another guru folder', function () {
    $guru = User::factory()->guru()->create();
    $foreignFolder = Folder::factory()->create();

    $this->actingAs($guru)
        ->get(route('library.index', ['folder' => $foreignFolder->id]))
        ->assertNotFound();
});

test('owner can move an item into their folder', function () {
    $guru = User::factory()->guru()->create();
    $folder = Folder::factory()->create(['user_id' => $guru->id]);
    $item = LearningItem::factory()->create(['user_id' => $guru->id]);

    $this->actingAs($guru)
        ->patch(route('library.move', $item), ['folder_id' => $folder->id])
        ->assertRedirect();

    expect($item->refresh()->folder_id)->toBe($folder->id);
});

test('non-owner cannot move an item', function () {
    $colleague = User::factory()->guru()->create();
    $item = LearningItem::factory()->hasAttached($colleague, relationship: 'sharedWith')->create();

    $this->actingAs($colleague)
        ->patch(route('library.move', $item), ['folder_id' => null])
        ->assertForbidden();
});

test('guru can copy a shared item with its file into their own library', function () {
    Storage::fake(LearningItem::ATTACHMENT_DISK);
    $colleague = User::factory()->guru()->create();
    $path = UploadedFile::fake()->create('rpp.docx', 40)->store('library/1', LearningItem::ATTACHMENT_DISK);
    $item = LearningItem::factory()->hasAttached($colleague, relationship: 'sharedWith')->create([
        'title' => 'RPP Semester 1',
        'attachment_name' => 'rpp.docx',
        'attachment_path' => $path,
    ]);

    $this->actingAs($colleague)->post(route('library.copy', $item))->assertSessionHas('success');

    $copy = LearningItem::where('user_id', $colleague->id)->sole();
    expect($copy->title)->toBe('Salinan - RPP Semester 1')
        ->and($copy->attachment_path)->not->toBe($path)
        ->and($copy->classes()->count())->toBe(0);
    Storage::disk(LearningItem::ATTACHMENT_DISK)->assertExists([$path, $copy->attachment_path]);
});

test('guru cannot copy an item that was not shared with them', function () {
    $guru = User::factory()->guru()->create();
    $item = LearningItem::factory()->create();

    $this->actingAs($guru)->post(route('library.copy', $item))->assertForbidden();
    expect(LearningItem::where('user_id', $guru->id)->exists())->toBeFalse();
});

test('enrolled siswa can download an attachment but outsiders cannot', function () {
    Storage::fake(LearningItem::ATTACHMENT_DISK);
    $class = SchoolClass::factory()->create();
    $enrolled = User::factory()->siswa()->create();
    $outsider = User::factory()->siswa()->create();
    $class->students()->attach($enrolled->id);
    $path = UploadedFile::fake()->create('modul.pdf', 20)->store('library/1', LearningItem::ATTACHMENT_DISK);
    $item = LearningItem::factory()->hasAttached($class, relationship: 'classes')->create([
        'attachment_name' => 'modul.pdf',
        'attachment_path' => $path,
    ]);

    $this->actingAs($enrolled)->get(route('library.download', $item))
        ->assertOk()
        ->assertDownload('modul.pdf');
    $this->actingAs($outsider)->get(route('library.download', $item))->assertForbidden();
});

test('permanently deleting an item removes its stored file', function () {
    Storage::fake(LearningItem::ATTACHMENT_DISK);
    $guru = User::factory()->guru()->create();
    $path = UploadedFile::fake()->create('lama.pdf', 20)->store('library/1', LearningItem::ATTACHMENT_DISK);
    $item = LearningItem::factory()->create(['user_id' => $guru->id, 'attachment_path' => $path]);
    $item->delete();

    $this->actingAs($guru)->delete(route('trash.force-delete', ['kind' => 'item', 'id' => $item->id]));

    Storage::disk(LearningItem::ATTACHMENT_DISK)->assertMissing($path);
});

test('guru can create, rename and delete an empty folder', function () {
    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)->post(route('folders.store'), ['name' => 'Semester 1'])->assertSessionHas('success');
    $folder = Folder::where('user_id', $guru->id)->sole();

    $this->actingAs($guru)->patch(route('folders.update', $folder), ['name' => 'Semester Ganjil']);
    expect($folder->refresh()->name)->toBe('Semester Ganjil');

    $this->actingAs($guru)->delete(route('folders.destroy', $folder));
    expect(Folder::find($folder->id))->toBeNull();
});

test('a folder that still has content is not deleted', function () {
    $guru = User::factory()->guru()->create();
    $folder = Folder::factory()->create(['user_id' => $guru->id]);
    LearningItem::factory()->create(['user_id' => $guru->id, 'folder_id' => $folder->id]);

    $this->actingAs($guru)->delete(route('folders.destroy', $folder))->assertSessionHas('error');

    expect(Folder::find($folder->id))->not->toBeNull();
});

test('guru cannot rename or delete another guru folder', function () {
    $guru = User::factory()->guru()->create();
    $foreignFolder = Folder::factory()->create(['name' => 'Milik Orang']);

    $this->actingAs($guru)->patch(route('folders.update', $foreignFolder), ['name' => 'Dibajak'])->assertForbidden();
    $this->actingAs($guru)->delete(route('folders.destroy', $foreignFolder))->assertForbidden();

    expect($foreignFolder->refresh()->name)->toBe('Milik Orang');
});

test('siswa cannot create folders or library items', function () {
    $siswa = User::factory()->siswa()->create();

    $this->actingAs($siswa)->post(route('folders.store'), ['name' => 'Folder Siswa'])->assertForbidden();
    $this->actingAs($siswa)->post(route('library.store'), ['type' => 'material', 'title' => 'X'])->assertForbidden();
});

test('guru of a class can grade a submission on an item an admin shared to that class', function () {
    $guru = User::factory()->guru()->create();
    $class = SchoolClass::factory()->create(['guru_id' => $guru->id]);
    $siswa = User::factory()->siswa()->create();
    $class->students()->attach($siswa->id);
    $item = LearningItem::factory()->hasAttached($class, relationship: 'classes')->create([
        'user_id' => User::factory()->admin(),
        'type' => 'assignment',
    ]);
    $submission = $item->submissions()->create(['siswa_id' => $siswa->id, 'answer_text' => 'Jawaban', 'submitted_at' => now()]);

    $this->actingAs($guru)->post(route('tasks.grade', $submission), ['score' => 80])->assertSessionHas('success');

    expect($submission->refresh()->score)->toBe(80);
});

test('siswa cannot submit to an assignment outside their classes', function () {
    $siswa = User::factory()->siswa()->create();
    $item = LearningItem::factory()->hasAttached(SchoolClass::factory(), relationship: 'classes')->create(['type' => 'assignment']);

    $this->actingAs($siswa)->post(route('tasks.submit', $item), ['answer_text' => 'Coba-coba'])->assertForbidden();
});
