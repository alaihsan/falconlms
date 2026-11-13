<?php

use App\Models\AssignmentSubmission;
use App\Models\LearningItem;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;

/**
 * Create a class with its teacher, one enrolled student and one graded item shared to it.
 *
 * @return array{0: User, 1: SchoolClass, 2: User, 3: LearningItem}
 */
function gradebookFixture(): array
{
    $guru = User::factory()->guru()->create();
    $class = SchoolClass::factory()->create(['guru_id' => $guru->id]);
    $siswa = User::factory()->siswa()->create(['username' => 'siswa01']);
    $class->students()->attach($siswa->id);
    $item = LearningItem::factory()->graded()->hasAttached($class, relationship: 'classes')->create([
        'user_id' => $guru->id,
        'title' => 'Ulangan Harian 1',
        'max_score' => 100,
    ]);

    return [$guru, $class, $siswa, $item];
}

/**
 * Build an uploaded gradebook CSV from rows.
 *
 * @param  array<int, array<int, string>>  $rows
 */
function gradebookCsv(array $rows, string $delimiter = ','): UploadedFile
{
    $lines = array_map(fn (array $row): string => implode($delimiter, $row), $rows);

    return UploadedFile::fake()->createWithContent('nilai.csv', implode("\n", $lines));
}

test('guru sees the gradebook grid with only graded items of the selected class', function () {
    [$guru, $class, $siswa, $item] = gradebookFixture();
    LearningItem::factory()->hasAttached($class, relationship: 'classes')->create(['type' => 'material', 'is_graded' => false]);
    AssignmentSubmission::factory()->create(['learning_item_id' => $item->id, 'siswa_id' => $siswa->id, 'score' => 88]);

    $response = $this->actingAs($guru)->get(route('grades.index', ['class_id' => $class->id]));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Grades/Book')
        ->where('selectedClassId', $class->id)
        ->has('students', 1)
        ->has('items', 1)
        ->where('items.0.id', $item->id)
        ->where("scores.{$siswa->id}:{$item->id}", 88));
});

test('guru can enter a score for a student who never submitted an answer', function () {
    [$guru, $class, $siswa, $item] = gradebookFixture();

    $response = $this->actingAs($guru)->put(route('grades.update', $class), [
        'scores' => [['learning_item_id' => $item->id, 'siswa_id' => $siswa->id, 'score' => 75]],
    ]);

    $response->assertRedirect()->assertSessionHas('success');
    $this->assertDatabaseHas('assignment_submissions', [
        'learning_item_id' => $item->id,
        'siswa_id' => $siswa->id,
        'score' => 75,
        'submitted_at' => null,
    ]);
});

test('clearing a score keeps the student answer but removes score-only rows', function () {
    [$guru, $class, $siswa, $item] = gradebookFixture();
    $otherSiswa = User::factory()->siswa()->create();
    $class->students()->attach($otherSiswa->id);
    $answered = AssignmentSubmission::factory()->create([
        'learning_item_id' => $item->id, 'siswa_id' => $siswa->id, 'score' => 60, 'submitted_at' => now(),
    ]);
    $scoreOnly = AssignmentSubmission::factory()->create([
        'learning_item_id' => $item->id, 'siswa_id' => $otherSiswa->id, 'score' => 70, 'submitted_at' => null, 'answer_text' => null,
    ]);

    $this->actingAs($guru)->put(route('grades.update', $class), [
        'scores' => [
            ['learning_item_id' => $item->id, 'siswa_id' => $siswa->id, 'score' => null],
            ['learning_item_id' => $item->id, 'siswa_id' => $otherSiswa->id, 'score' => null],
        ],
    ])->assertSessionHas('success');

    expect($answered->refresh()->score)->toBeNull()
        ->and($answered->graded_at)->toBeNull()
        ->and(AssignmentSubmission::find($scoreOnly->id))->toBeNull();
});

test('a score above the item maximum rejects the whole batch', function () {
    [$guru, $class, $siswa, $item] = gradebookFixture();
    $item->update(['max_score' => 50]);

    $response = $this->actingAs($guru)->put(route('grades.update', $class), [
        'scores' => [['learning_item_id' => $item->id, 'siswa_id' => $siswa->id, 'score' => 51]],
    ]);

    $response->assertSessionHasErrors('scores');
    $this->assertDatabaseCount('assignment_submissions', 0);
});

test('scores for students outside the class or ungraded items are rejected', function (string $case) {
    [$guru, $class, $siswa, $item] = gradebookFixture();
    $outsider = User::factory()->siswa()->create();
    $ungraded = LearningItem::factory()->hasAttached($class, relationship: 'classes')->create(['type' => 'material', 'is_graded' => false]);

    $entry = match ($case) {
        'outsider' => ['learning_item_id' => $item->id, 'siswa_id' => $outsider->id, 'score' => 80],
        'ungraded' => ['learning_item_id' => $ungraded->id, 'siswa_id' => $siswa->id, 'score' => 80],
    };

    $this->actingAs($guru)
        ->put(route('grades.update', $class), ['scores' => [$entry]])
        ->assertSessionHasErrors('scores');
    $this->assertDatabaseCount('assignment_submissions', 0);
})->with(['outsider', 'ungraded']);

test('guru cannot edit, export or import the gradebook of a class they do not teach', function () {
    [, $class, $siswa, $item] = gradebookFixture();
    $otherGuru = User::factory()->guru()->create();

    $this->actingAs($otherGuru)->put(route('grades.update', $class), [
        'scores' => [['learning_item_id' => $item->id, 'siswa_id' => $siswa->id, 'score' => 100]],
    ])->assertForbidden();
    $this->actingAs($otherGuru)->get(route('grades.export', $class))->assertForbidden();
    $this->actingAs($otherGuru)->post(route('grades.import', $class), [
        'file' => gradebookCsv([['Username', 'Nama', "X [#{$item->id}]"], ['siswa01', 'A', '100']]),
    ])->assertForbidden();

    $this->assertDatabaseCount('assignment_submissions', 0);
});

test('siswa cannot edit the gradebook and sees only their own grade history', function () {
    [, $class, $siswa, $item] = gradebookFixture();
    $classmate = User::factory()->siswa()->create();
    AssignmentSubmission::factory()->create(['learning_item_id' => $item->id, 'siswa_id' => $siswa->id, 'score' => 90]);
    AssignmentSubmission::factory()->create(['learning_item_id' => $item->id, 'siswa_id' => $classmate->id, 'score' => 40]);

    $this->actingAs($siswa)->put(route('grades.update', $class), [
        'scores' => [['learning_item_id' => $item->id, 'siswa_id' => $siswa->id, 'score' => 100]],
    ])->assertForbidden();

    $this->actingAs($siswa)->get(route('grades.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Grades/Index')
        ->has('grades', 1)
        ->where('grades.0.score', 90));
});

test('export produces a csv with one row per student and one column per graded item', function () {
    [$guru, $class, $siswa, $item] = gradebookFixture();
    AssignmentSubmission::factory()->create(['learning_item_id' => $item->id, 'siswa_id' => $siswa->id, 'score' => 82]);

    $response = $this->actingAs($guru)->get(route('grades.export', $class));

    $response->assertOk()->assertDownload();
    $csv = $response->streamedContent();
    expect($csv)->toContain("Ulangan Harian 1 (maks 100) [#{$item->id}]")
        ->and($csv)->toContain("siswa01,\"{$siswa->name}\",82");
});

test('import reads an exported layout with semicolons and skips invalid cells and unknown students', function () {
    [$guru, $class, $siswa, $item] = gradebookFixture();
    $second = User::factory()->siswa()->create(['username' => 'siswa02']);
    $class->students()->attach($second->id);

    $response = $this->actingAs($guru)->post(route('grades.import', $class), [
        'file' => gradebookCsv([
            ['Username', 'Nama', "Ulangan Harian 1 (maks 100) [#{$item->id}]"],
            ['siswa01', 'Satu', '91'],
            ['siswa02', 'Dua', '150'],
            ['tidak-ada', 'Asing', '70'],
        ], ';'),
    ]);

    $response->assertSessionHas('success');
    expect(AssignmentSubmission::where('siswa_id', $siswa->id)->sole()->score)->toBe(91)
        ->and(AssignmentSubmission::where('siswa_id', $second->id)->exists())->toBeFalse()
        ->and(AssignmentSubmission::count())->toBe(1);
});

test('import refuses a file without recognizable score columns', function () {
    [$guru, $class] = gradebookFixture();

    $response = $this->actingAs($guru)->post(route('grades.import', $class), [
        'file' => gradebookCsv([['Username', 'Nama', 'Nilai'], ['siswa01', 'Satu', '91']]),
    ]);

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('assignment_submissions', 0);
});

test('owner can toggle whether an item enters the gradebook', function () {
    $guru = User::factory()->guru()->create();
    $item = LearningItem::factory()->create(['user_id' => $guru->id, 'type' => 'material', 'is_graded' => false]);

    $this->actingAs($guru)->patch(route('library.toggle-graded', $item))->assertSessionHas('success');
    expect($item->refresh()->is_graded)->toBeTrue();

    $this->actingAs(User::factory()->guru()->create())
        ->patch(route('library.toggle-graded', $item))
        ->assertForbidden();
    expect($item->refresh()->is_graded)->toBeTrue();
});

test('assignments enter the gradebook by default while materials do not', function () {
    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)->post(route('library.store'), ['type' => 'assignment', 'title' => 'Tugas Baru']);
    $this->actingAs($guru)->post(route('library.store'), ['type' => 'material', 'title' => 'Materi Baru']);
    $this->actingAs($guru)->post(route('library.store'), ['type' => 'material', 'title' => 'Materi Dinilai', 'is_graded' => true]);

    expect(LearningItem::where('title', 'Tugas Baru')->sole()->is_graded)->toBeTrue()
        ->and(LearningItem::where('title', 'Materi Baru')->sole()->is_graded)->toBeFalse()
        ->and(LearningItem::where('title', 'Materi Dinilai')->sole()->is_graded)->toBeTrue();
});
