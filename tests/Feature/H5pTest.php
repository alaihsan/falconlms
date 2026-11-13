<?php

use App\Models\LearningItem;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

/**
 * Build an uploaded .h5p package from a map of entry names to contents.
 *
 * @param  array<string, string>|null  $entries  Defaults to a minimal valid package.
 */
function h5pPackage(?array $entries = null, string $name = 'kuis.h5p'): UploadedFile
{
    $entries ??= [
        'h5p.json' => json_encode(['title' => 'Kuis', 'mainLibrary' => 'H5P.Halo', 'preloadedDependencies' => []]),
        'content/content.json' => json_encode(['text' => 'Halo']),
        'H5P.Halo-1.0/library.json' => json_encode(['machineName' => 'H5P.Halo']),
        'H5P.Halo-1.0/halo.js' => 'var H5P = H5P || {};',
    ];

    $path = tempnam(sys_get_temp_dir(), 'h5p');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    foreach ($entries as $entryName => $contents) {
        $zip->addFromString($entryName, $contents);
    }
    $zip->close();

    return new UploadedFile($path, $name, 'application/zip', null, true);
}

/**
 * Upload a valid H5P package as the given guru and return the created item.
 */
function uploadH5p(User $guru, ?UploadedFile $package = null): LearningItem
{
    test()->actingAs($guru)->post(route('library.store'), [
        'type' => 'h5p',
        'title' => 'Kuis Interaktif',
        'attachment' => $package ?? h5pPackage(),
    ])->assertSessionHas('success');

    return LearningItem::where('title', 'Kuis Interaktif')->sole();
}

beforeEach(function () {
    Storage::fake(LearningItem::ATTACHMENT_DISK);
});

test('guru can upload an h5p package and it is extracted', function () {
    $item = uploadH5p(User::factory()->guru()->create());

    Storage::disk(LearningItem::ATTACHMENT_DISK)->assertExists([
        $item->attachment_path,
        $item->h5pDirectory().'/h5p.json',
        $item->h5pDirectory().'/content/content.json',
        $item->h5pDirectory().'/H5P.Halo-1.0/halo.js',
    ]);
});

test('files with disallowed extensions are dropped during extraction', function () {
    $item = uploadH5p(User::factory()->guru()->create(), h5pPackage([
        'h5p.json' => '{}',
        'content/content.json' => '{}',
        'content/shell.php' => '<?php echo 1;',
        'content/halaman.html' => '<script>alert(1)</script>',
    ]));

    $disk = Storage::disk(LearningItem::ATTACHMENT_DISK);
    $disk->assertExists($item->h5pDirectory().'/h5p.json');
    $disk->assertMissing([$item->h5pDirectory().'/content/shell.php', $item->h5pDirectory().'/content/halaman.html']);
});

test('invalid h5p uploads are rejected and leave nothing behind', function (Closure $package) {
    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)->post(route('library.store'), [
        'type' => 'h5p',
        'title' => 'Paket Rusak',
        'attachment' => $package(),
    ])->assertSessionHasErrors('attachment');

    $this->assertDatabaseMissing('learning_items', ['title' => 'Paket Rusak']);
    expect(Storage::disk(LearningItem::ATTACHMENT_DISK)->allFiles())->toBe([]);
})->with([
    'missing file' => [fn () => null],
    'not a zip' => [fn () => UploadedFile::fake()->createWithContent('kuis.h5p', 'bukan zip')],
    'zip without h5p.json' => [fn () => h5pPackage(['content/content.json' => '{}'])],
    'wrong extension' => [fn () => h5pPackage(name: 'kuis.zip')],
    'path traversal entry' => [fn () => h5pPackage([
        'h5p.json' => '{}',
        'content/content.json' => '{}',
        '../../keluar.js' => 'x',
    ])],
]);

test('library exposes a player url that serves the player page and package files without a session', function () {
    $guru = User::factory()->guru()->create();
    $item = uploadH5p($guru);

    $playerUrl = null;
    $this->actingAs($guru)->get(route('library.index'))->assertInertia(function (AssertableInertia $page) use (&$playerUrl) {
        $playerUrl = $page->toArray()['props']['items'][0]['h5p_player_url'];
    });
    auth()->logout();

    $this->get($playerUrl)
        ->assertOk()
        ->assertHeader('Content-Security-Policy', 'sandbox allow-scripts')
        ->assertSee('H5PStandalone');

    $this->get(str_replace('/player', '/content/h5p.json', $playerUrl))
        ->assertOk()
        ->assertHeader('Content-Security-Policy', 'sandbox allow-scripts')
        ->assertHeader('Access-Control-Allow-Origin', '*');
});

test('player urls with a tampered or expired signature are refused', function () {
    $item = uploadH5p(User::factory()->guru()->create());
    $other = LearningItem::factory()->create(['type' => 'h5p', 'attachment_path' => 'library/1/lain.zip']);
    $expires = now()->addHour()->getTimestamp();
    $past = now()->subMinute()->getTimestamp();

    $this->get(route('h5p.player', [$item, $expires, 'salah']))->assertForbidden();
    $this->get(route('h5p.player', [$other, $expires, $item->h5pSignature($expires)]))->assertForbidden();
    $this->get(route('h5p.player', [$item, $past, $item->h5pSignature($past)]))->assertForbidden();
    $this->get(route('h5p.content', [$item, $expires + 60, $item->h5pSignature($expires), 'h5p.json']))->assertForbidden();
});

test('content route does not serve files outside the package', function (string $path) {
    $item = uploadH5p(User::factory()->guru()->create());
    $expires = now()->addHour()->getTimestamp();

    $this->get("/h5p/{$item->id}/{$expires}/{$item->h5pSignature($expires)}/content/{$path}")->assertNotFound();
})->with([
    'parent directory' => ['../'.'../'.'../.env'],
    'encoded traversal' => ['..%2F..%2F.env'],
    'missing file' => ['tidak-ada.json'],
    'disallowed type' => ['h5p.php'],
]);

test('siswa only receives a player url for h5p items shared to their class', function () {
    $guru = User::factory()->guru()->create();
    $item = uploadH5p($guru);
    $class = SchoolClass::factory()->create(['guru_id' => $guru->id]);
    $siswa = User::factory()->siswa()->create();
    $class->students()->attach($siswa->id);

    $this->actingAs($siswa)->get(route('library.index'))->assertInertia(fn (AssertableInertia $page) => $page->has('items', 0));

    $item->classes()->attach($class->id);

    $this->actingAs($siswa)->get(route('library.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->has('items', 1)
        ->where('items.0.h5p_player_url', fn (string $url) => str_contains($url, "/h5p/{$item->id}/")));
});

test('player assets are served from the bundled player only', function () {
    $this->get(route('h5p.asset', ['path' => 'main.bundle.js']))->assertOk()->assertHeader('Access-Control-Allow-Origin', '*');
    $this->get('/h5p-player/..%2F..%2F.env')->assertNotFound();
    $this->get(route('h5p.asset', ['path' => 'h5p.d.ts']))->assertNotFound();
});

test('copying an h5p item extracts its own copy and permanent deletion removes the package', function () {
    $guru = User::factory()->guru()->create();
    $item = uploadH5p($guru);

    $this->actingAs($guru)->post(route('library.copy', $item))->assertSessionHas('success');
    $copy = LearningItem::where('title', 'like', 'Salinan - %')->sole();
    $disk = Storage::disk(LearningItem::ATTACHMENT_DISK);

    expect($copy->h5pDirectory())->not->toBe($item->h5pDirectory());
    $disk->assertExists($copy->h5pDirectory().'/h5p.json');

    $directory = $item->h5pDirectory();
    $item->forceDelete();

    $disk->assertMissing($directory.'/h5p.json');
    $disk->assertExists($copy->h5pDirectory().'/h5p.json');
});
