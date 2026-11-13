<?php

use App\Models\AssignmentSubmission;
use App\Models\LearningItem;
use App\Models\SchoolClass;
use App\Models\User;

test('guru can create school class', function () {
    $guru = User::factory()->guru()->create();

    $response = $this->actingAs($guru)->post(route('classes.store'), [
        'name' => 'XI RPL 2',
        'subject' => 'Basis Data',
        'room' => 'Lab Basis Data',
        'description' => 'Mempelajari MySQL dan Normalisasi',
    ]);

    $response->assertRedirect(route('classes.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('school_classes', [
        'name' => 'XI RPL 2',
        'subject' => 'Basis Data',
        'guru_id' => $guru->id,
    ]);
});

test('siswa cannot create school class', function () {
    $siswa = User::factory()->siswa()->create();

    $response = $this->actingAs($siswa)->post(route('classes.store'), [
        'name' => 'Kelas Ilegal',
        'subject' => 'Hacking',
    ]);

    $response->assertForbidden();
});

test('siswa can join class with valid code', function () {
    $guru = User::factory()->guru()->create();
    $class = SchoolClass::factory()->create([
        'guru_id' => $guru->id,
        'code' => 'TEST01',
    ]);

    $siswa = User::factory()->siswa()->create();

    $response = $this->actingAs($siswa)->post(route('classes.join'), [
        'code' => 'TEST01',
    ]);

    $response->assertRedirect(route('classes.index'));
    $response->assertSessionHas('success');

    expect($class->students()->where('users.id', $siswa->id)->exists())->toBeTrue();
});

test('guru can create material in library', function () {
    $guru = User::factory()->guru()->create();
    $class = SchoolClass::factory()->create(['guru_id' => $guru->id]);

    $response = $this->actingAs($guru)->post(route('library.store'), [
        'class_ids' => [$class->id],
        'type' => 'material',
        'title' => 'Pengenalan SQL',
        'topic' => 'Bab 1',
        'content' => 'SQL adalah Structured Query Language.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('learning_items', [
        'title' => 'Pengenalan SQL',
        'user_id' => $guru->id,
        'type' => 'material',
    ]);
});

test('siswa can submit assignment and guru can grade it', function () {
    $guru = User::factory()->guru()->create();
    $class = SchoolClass::factory()->create(['guru_id' => $guru->id]);
    $siswa = User::factory()->siswa()->create();
    $class->students()->attach($siswa->id);

    $assignment = LearningItem::factory()->hasAttached($class, relationship: 'classes')->create([
        'user_id' => $guru->id,
        'type' => 'assignment',
        'title' => 'Tugas ERD',
        'max_score' => 100,
    ]);

    // Siswa submit
    $subResponse = $this->actingAs($siswa)->post(route('tasks.submit', $assignment), [
        'answer_text' => 'Ini jawaban ERD saya.',
    ]);
    $subResponse->assertRedirect();
    $subResponse->assertSessionHas('success');

    $submission = AssignmentSubmission::where('learning_item_id', $assignment->id)
        ->where('siswa_id', $siswa->id)
        ->first();
    expect($submission)->not->toBeNull();
    expect($submission->answer_text)->toBe('Ini jawaban ERD saya.');

    // Guru grade
    $gradeResponse = $this->actingAs($guru)->post(route('tasks.grade', $submission), [
        'score' => 90,
        'feedback' => 'Bagus sekali!',
    ]);
    $gradeResponse->assertRedirect();
    $gradeResponse->assertSessionHas('success');

    $submission->refresh();
    expect($submission->score)->toBe(90);
    expect($submission->feedback)->toBe('Bagus sekali!');
});

test('soft-deleted class and item can be restored or force-deleted', function () {
    $guru = User::factory()->guru()->create();
    $class = SchoolClass::factory()->create(['guru_id' => $guru->id]);

    // Soft delete
    $this->actingAs($guru)->delete(route('classes.destroy', $class));
    expect(SchoolClass::find($class->id))->toBeNull();
    expect(SchoolClass::withTrashed()->find($class->id))->not->toBeNull();

    // Restore
    $this->actingAs($guru)->post(route('trash.restore', ['kind' => 'class', 'id' => $class->id]));
    expect(SchoolClass::find($class->id))->not->toBeNull();

    // Delete again then force delete
    $this->actingAs($guru)->delete(route('classes.destroy', $class));
    $this->actingAs($guru)->delete(route('trash.force-delete', ['kind' => 'class', 'id' => $class->id]));
    expect(SchoolClass::withTrashed()->find($class->id))->toBeNull();
});
