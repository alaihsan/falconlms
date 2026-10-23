<?php

use App\Enums\ItemType;
use App\Models\Folder;
use App\Models\Item;
use App\Models\Space;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\ClassworkPosted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->teacher = User::factory()->teacher()->create();
    $this->library = Space::personalLibraryFor($this->teacher);
});

test('a material is saved at the root of the library and its HTML is sanitized', function () {
    $this->actingAs($this->teacher)->post(route('items.store', $this->library), [
        'type' => 'material',
        'title' => 'Rumus dasar',
        'body' => '<p onclick="steal()">Halo <strong>siswa</strong></p><script>alert(1)</script><a href="javascript:alert(1)">x</a><a href="https://sekolah.lan/x">y</a>',
    ])->assertSessionHasNoErrors()->assertRedirect(route('library.show'));

    $item = Item::query()->sole();

    expect($item->folder_id)->toBeNull()
        ->and($item->isPublished())->toBeTrue()
        ->and($item->body)->not->toContain('script')
        ->and($item->body)->not->toContain('onclick')
        ->and($item->body)->not->toContain('javascript:')
        ->and($item->body)->toContain('<strong>siswa</strong>')
        ->and($item->body)->toContain('href="https://sekolah.lan/x"')
        ->and($item->body)->toContain('rel="noopener noreferrer"');
});

test('links must use http or https', function () {
    $this->actingAs($this->teacher)->post(route('items.store', $this->library), [
        'type' => 'link',
        'title' => 'Berbahaya',
        'url' => 'javascript:alert(1)',
    ])->assertSessionHasErrors('url');

    $this->actingAs($this->teacher)->post(route('items.store', $this->library), [
        'type' => 'link',
        'title' => 'GeoGebra lokal',
        'url' => 'http://geogebra.sekolah.lan',
    ])->assertSessionHasNoErrors();

    expect(Item::query()->sole()->type)->toBe(ItemType::Link);
});

test('content can not be saved into a folder of another space', function () {
    $foreign = Folder::factory()->create(['space_id' => Space::personalLibraryFor(User::factory()->teacher()->create())->id]);

    $this->actingAs($this->teacher)->post(route('items.store', $this->library), [
        'type' => 'material',
        'title' => 'x',
        'folder_id' => $foreign->id,
    ])->assertSessionHasErrors('folder_id');
});

test('a posted assignment notifies the students and lands at the top of its topic', function () {
    Notification::fake();
    $students = User::factory()->count(2)->create();
    $course = Space::factory()->withTeacher($this->teacher)->withStudents($students)->create();
    $topic = Topic::factory()->create(['space_id' => $course->id]);
    $older = Item::factory()->inTopic($topic)->create(['position' => 0]);

    $this->actingAs($this->teacher)->post(route('items.store', $course), [
        'type' => 'assignment',
        'title' => 'Latihan 1',
        'topic_id' => $topic->id,
        'due_at' => '2026-11-01T23:59',
        'max_points' => 100,
        'publish' => true,
    ])->assertSessionHasNoErrors()->assertRedirect(route('courses.classwork', $course));

    $item = Item::query()->where('title', 'Latihan 1')->sole();

    expect($item->position)->toBe(0)
        ->and($older->refresh()->position)->toBe(1)
        ->and($item->due_at->format('Y-m-d H:i'))->toBe('2026-11-01 23:59');

    Notification::assertSentTo($students, ClassworkPosted::class);
});

test('a class only accepts materials and assignments in its own topics', function () {
    $course = Space::factory()->withTeacher($this->teacher)->create();
    $foreignTopic = Topic::factory()->create();

    $this->actingAs($this->teacher)->post(route('items.store', $course), ['type' => 'link', 'title' => 'x', 'url' => 'http://a.lan'])
        ->assertSessionHasErrors('type');

    $this->actingAs($this->teacher)->post(route('items.store', $course), ['type' => 'material', 'title' => 'x', 'topic_id' => $foreignTopic->id])
        ->assertSessionHasErrors('topic_id');
});

test('drafts are hidden from students until they are posted', function () {
    Notification::fake();
    $student = User::factory()->create();
    $course = Space::factory()->withTeacher($this->teacher)->withStudents([$student])->create();
    $draft = Item::factory()->draft()->create(['space_id' => $course->id]);

    $this->actingAs($student)->get(route('items.show', $draft))->assertForbidden();

    $this->actingAs($this->teacher)->post(route('items.publish', $draft));

    $this->actingAs($student)->get(route('items.show', $draft))->assertOk();
    Notification::assertSentTo($student, ClassworkPosted::class);
});

test('"also save to my library" keeps an independent copy with its files', function () {
    $course = Space::factory()->withTeacher($this->teacher)->create();

    $this->actingAs($this->teacher)->post(route('items.store', $course), [
        'type' => 'material',
        'title' => 'Modul trigonometri',
        'files' => [UploadedFile::fake()->create('modul.pdf', 50, 'application/pdf')],
        'also_save_to_library' => true,
        'publish' => true,
    ])->assertSessionHasNoErrors();

    $original = Item::query()->where('space_id', $course->id)->with('attachments')->sole();
    $copy = Item::query()->where('space_id', $this->library->id)->with('attachments')->sole();

    expect($copy->source_item_id)->toBe($original->id)
        ->and($copy->attachments)->toHaveCount(1)
        ->and($copy->attachments->first()->path)->not->toBe($original->attachments->first()->path);

    $original->attachments->first()->delete();
    Storage::disk('local')->assertExists($copy->attachments->first()->path);
});

test('an item can be edited, its attachments removed, and deleted', function () {
    $item = Item::factory()->create(['space_id' => $this->library->id]);

    $this->actingAs($this->teacher)->put(route('items.update', $item), [
        'title' => 'Judul baru',
        'body' => '<p>Isi</p>',
        'files' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')],
    ])->assertSessionHasNoErrors();

    $attachment = $item->refresh()->attachments()->sole();
    expect($item->title)->toBe('Judul baru');

    $this->actingAs($this->teacher)->put(route('items.update', $item), [
        'title' => 'Judul baru',
        'remove_attachments' => [$attachment->id],
    ]);

    expect($item->attachments()->count())->toBe(0);
    Storage::disk('local')->assertMissing($attachment->path);

    $this->actingAs($this->teacher)->delete(route('items.destroy', $item))->assertRedirect(route('library.show'));
    expect(Item::query()->count())->toBe(0);
});

test('other users can not see or change library content', function () {
    $item = Item::factory()->create(['space_id' => $this->library->id]);
    $other = User::factory()->teacher()->create();

    $this->actingAs($other)->get(route('items.show', $item))->assertForbidden();
    $this->actingAs($other)->put(route('items.update', $item), ['title' => 'x'])->assertForbidden();
    $this->actingAs($other)->delete(route('items.destroy', $item))->assertForbidden();
});
