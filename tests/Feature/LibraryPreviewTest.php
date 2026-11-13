<?php

use App\Models\LearningItem;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

/**
 * Create an item owned by the guru with a stored attachment of the given file name.
 */
function itemWithAttachment(User $guru, string $fileName): LearningItem
{
    $path = UploadedFile::fake()->create($fileName, 10)->store('library/1', LearningItem::ATTACHMENT_DISK);

    return LearningItem::factory()->create([
        'user_id' => $guru->id,
        'attachment_name' => $fileName,
        'attachment_path' => $path,
    ]);
}

beforeEach(function () {
    Storage::fake(LearningItem::ATTACHMENT_DISK);
});

test('browser-native files are served inline with their content type', function (string $fileName, string $contentType) {
    $guru = User::factory()->guru()->create();
    $item = itemWithAttachment($guru, $fileName);

    $response = $this->actingAs($guru)->get(route('library.preview', $item));

    $response->assertOk()
        ->assertHeader('Content-Type', $contentType)
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline');
})->with([
    'pdf' => ['modul.pdf', 'application/pdf'],
    'image' => ['foto.jpg', 'image/jpeg'],
    'video' => ['video.mp4', 'video/mp4'],
    'audio' => ['audio.mp3', 'audio/mpeg'],
]);

test('office documents are converted to pdf once and the rendition is reused', function () {
    $guru = User::factory()->guru()->create();
    $item = itemWithAttachment($guru, 'rpp.docx');
    Process::fake(function ($process) {
        $outputDirectory = $process->command[array_search('--outdir', $process->command, true) + 1];
        $source = $process->command[array_key_last($process->command)];
        file_put_contents($outputDirectory.'/'.pathinfo($source, PATHINFO_FILENAME).'.pdf', '%PDF-1.4 konversi');

        return Process::result();
    });

    $first = $this->actingAs($guru)->get(route('library.preview', $item));
    $second = $this->actingAs($guru)->get(route('library.preview', $item));

    $first->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $second->assertOk();
    Process::assertRanTimes(fn ($process) => in_array('--convert-to', $process->command, true), 1);
    Storage::disk(LearningItem::ATTACHMENT_DISK)->assertExists($item->previewPath());
});

test('preview reports unavailable when the office conversion fails', function () {
    $guru = User::factory()->guru()->create();
    $item = itemWithAttachment($guru, 'slide.pptx');
    Process::fake(fn () => Process::result(errorOutput: 'soffice: command not found', exitCode: 127));

    $this->actingAs($guru)->get(route('library.preview', $item))->assertServiceUnavailable();

    Storage::disk(LearningItem::ATTACHMENT_DISK)->assertMissing($item->previewPath());
});

test('preview is forbidden for users who cannot see the item', function () {
    $item = itemWithAttachment(User::factory()->guru()->create(), 'modul.pdf');

    $this->actingAs(User::factory()->guru()->create())->get(route('library.preview', $item))->assertForbidden();
    $this->actingAs(User::factory()->siswa()->create())->get(route('library.preview', $item))->assertForbidden();
});

test('enrolled siswa can preview an attachment shared to their class', function () {
    $guru = User::factory()->guru()->create();
    $class = SchoolClass::factory()->create(['guru_id' => $guru->id]);
    $siswa = User::factory()->siswa()->create();
    $class->students()->attach($siswa->id);
    $item = itemWithAttachment($guru, 'modul.pdf');
    $item->classes()->attach($class->id);

    $this->actingAs($siswa)->get(route('library.preview', $item))->assertOk();
});

test('items without an attachment have no preview', function () {
    $guru = User::factory()->guru()->create();
    $item = LearningItem::factory()->create(['user_id' => $guru->id]);

    $this->actingAs($guru)->get(route('library.preview', $item))->assertNotFound();
});

test('library lists the preview kind of each attachment', function () {
    $guru = User::factory()->guru()->create();
    itemWithAttachment($guru, 'rpp.docx');

    $this->actingAs($guru)->get(route('library.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('items.0.preview_kind', 'office'));
});

test('permanently deleting an item also removes its pdf rendition', function () {
    $guru = User::factory()->guru()->create();
    $item = itemWithAttachment($guru, 'rpp.docx');
    Storage::disk(LearningItem::ATTACHMENT_DISK)->put($item->previewPath(), '%PDF-1.4');

    $item->forceDelete();

    Storage::disk(LearningItem::ATTACHMENT_DISK)->assertMissing($item->previewPath());
});
