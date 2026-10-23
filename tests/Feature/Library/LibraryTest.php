<?php

use App\Enums\ItemType;
use App\Models\Folder;
use App\Models\Item;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->teacher = User::factory()->teacher()->create();
    $this->library = Space::personalLibraryFor($this->teacher);
});

test('teachers see their own library', function () {
    $folder = Folder::factory()->create(['space_id' => $this->library->id, 'name' => 'Bab 1']);

    $this->actingAs($this->teacher)
        ->get(route('library.show'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('library/Show')
            ->where('folders.0.name', 'Bab 1')
            ->where('context.space.kind', 'personal_library')
            ->where('context.folder', null),
        );

    $this->actingAs($this->teacher)
        ->get(route('library.show', $folder))
        ->assertInertia(fn (Assert $page) => $page->where('context.folder.id', $folder->id));
});

test('another teacher\'s folder is reported as missing', function () {
    $other = User::factory()->teacher()->create();
    $folder = Folder::factory()->create(['space_id' => Space::personalLibraryFor($other)->id]);

    $this->actingAs($this->teacher)->get(route('library.show', $folder))->assertNotFound();
});

test('students have no library', function () {
    $this->actingAs(User::factory()->create())->get(route('library.show'))->assertForbidden();
});

test('folders can be created, nested, renamed and trashed with their content', function () {
    $this->actingAs($this->teacher)->post(route('folders.store', $this->library), ['name' => 'Bab 1'])->assertSessionHasNoErrors();
    $parent = Folder::query()->where('name', 'Bab 1')->sole();

    $this->actingAs($this->teacher)->post(route('folders.store', $this->library), ['name' => 'Video', 'parent_id' => $parent->id]);
    $child = Folder::query()->where('name', 'Video')->sole();
    expect($child->path)->toBe('/'.$parent->id.'/');

    $item = Item::factory()->create(['space_id' => $this->library->id, 'folder_id' => $child->id]);

    $this->actingAs($this->teacher)->patch(route('folders.update', $parent), ['name' => 'Bab Satu']);
    expect($parent->refresh()->name)->toBe('Bab Satu');

    $this->actingAs($this->teacher)->delete(route('folders.destroy', $parent));

    expect(Folder::query()->count())->toBe(0)
        ->and(Item::query()->whereKey($item->id)->exists())->toBeFalse()
        ->and(Item::withTrashed()->whereKey($item->id)->exists())->toBeTrue();
});

test('a folder can not be put inside another teacher\'s folder', function () {
    $foreign = Folder::factory()->create(['space_id' => Space::personalLibraryFor(User::factory()->teacher()->create())->id]);

    $this->actingAs($this->teacher)
        ->post(route('folders.store', $this->library), ['name' => 'x', 'parent_id' => $foreign->id])
        ->assertSessionHasErrors('parent_id');
});

test('nobody else can add to a library', function () {
    $this->actingAs(User::factory()->teacher()->create())
        ->post(route('folders.store', $this->library), ['name' => 'x'])
        ->assertForbidden();
});

test('uploaded files become file items in the current folder', function () {
    $folder = Folder::factory()->create(['space_id' => $this->library->id]);

    $this->actingAs($this->teacher)->post(route('uploads.store', $this->library), [
        'files' => [UploadedFile::fake()->create('Rumus.pdf', 100, 'application/pdf')],
        'folder_id' => $folder->id,
    ])->assertSessionHasNoErrors();

    $item = Item::query()->with('attachments')->sole();

    expect($item->type)->toBe(ItemType::File)
        ->and($item->title)->toBe('Rumus.pdf')
        ->and($item->folder_id)->toBe($folder->id);

    Storage::disk('local')->assertExists($item->attachments->first()->path);
});

test('uploading a folder recreates its sub-folders without escaping the target', function () {
    $this->actingAs($this->teacher)->post(route('uploads.store', $this->library), [
        'files' => [
            UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
        ],
        'paths' => ['Materi/Bab 1/a.pdf', '../../Materi/../b.pdf'],
    ])->assertSessionHasNoErrors();

    $materi = Folder::query()->where('name', 'Materi')->sole();
    $bab = Folder::query()->where('name', 'Bab 1')->sole();

    expect($bab->parent_id)->toBe($materi->id)
        ->and(Folder::query()->where('name', '..')->exists())->toBeFalse()
        ->and(Item::query()->where('title', 'b.pdf')->value('folder_id'))->toBe($materi->id);
});

test('dangerous file types are rejected, even when renamed', function (string $name, string $content) {
    // A real file (not a fake), so its type is detected from the content like a real upload.
    $path = tempnam(sys_get_temp_dir(), 'upload');
    file_put_contents($path, $content);

    $this->actingAs($this->teacher)->post(route('uploads.store', $this->library), [
        'files' => [new UploadedFile($path, $name, null, null, true)],
    ])->assertSessionHasErrors('files.0');

    expect(Item::query()->count())->toBe(0);
})->with([
    'html page' => ['halaman.html', '<!DOCTYPE html><html><body><script>alert(1)</script></body></html>'],
    'svg image' => ['gambar.svg', '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
    'php script' => ['shell.php', '<?php system($_GET["c"]);'],
    'php renamed to pdf' => ['tugas.pdf', '<?php system($_GET["c"]);'],
    'html renamed to jpg' => ['foto.jpg', '<!DOCTYPE html><html><body><script>alert(1)</script></body></html>'],
]);
