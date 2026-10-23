<?php

use App\Enums\SubmissionStatus;
use App\Models\Item;
use App\Models\Space;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionReturned;
use App\Notifications\SubmissionTurnedIn;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    $this->teacher = User::factory()->teacher()->create();
    $this->student = User::factory()->create();
    $this->course = Space::factory()->withTeacher($this->teacher)->withStudents([$this->student])->create();
    $this->assignment = Item::factory()->assignment()->create(['space_id' => $this->course->id, 'max_points' => 100]);
});

test('a student attaches work, turns it in and can unsubmit to change it', function () {
    $this->actingAs($this->student)->post(route('submissions.files.store', $this->assignment), [
        'files' => [UploadedFile::fake()->create('jawaban.pdf', 20, 'application/pdf')],
    ])->assertSessionHasNoErrors();

    $this->actingAs($this->student)->post(route('submissions.turn-in', $this->assignment), ['body' => 'Sudah selesai.']);

    $submission = Submission::query()->with('attachments')->sole();
    expect($submission->status)->toBe(SubmissionStatus::TurnedIn)
        ->and($submission->attachments)->toHaveCount(1);
    Notification::assertSentTo($this->teacher, SubmissionTurnedIn::class);

    $this->actingAs($this->student)->post(route('submissions.files.store', $this->assignment), [
        'files' => [UploadedFile::fake()->create('lagi.pdf', 20, 'application/pdf')],
    ])->assertSessionHasErrors('submission');

    $this->actingAs($this->student)->post(route('submissions.unsubmit', $this->assignment));
    expect($submission->refresh()->status)->toBe(SubmissionStatus::Draft);

    $this->actingAs($this->student)->delete(route('submissions.files.destroy', $submission->attachments->first()))->assertRedirect();
    expect($submission->attachments()->count())->toBe(0);
});

test('the score stays hidden until the teacher returns the work', function () {
    $this->actingAs($this->student)->post(route('submissions.turn-in', $this->assignment));

    $this->actingAs($this->teacher)->put(route('submissions.grade', [$this->assignment, $this->student]), ['score' => 88, 'feedback' => 'Bagus']);

    $this->actingAs($this->student)->get(route('items.show', $this->assignment))
        ->assertInertia(fn (Assert $page) => $page->where('submission.score', null)->where('submission.feedback', null));

    $this->actingAs($this->teacher)->put(route('submissions.grade', [$this->assignment, $this->student]), ['score' => 88, 'feedback' => 'Bagus', 'return' => true]);

    $this->actingAs($this->student)->get(route('items.show', $this->assignment))
        ->assertInertia(fn (Assert $page) => $page->where('submission.score', 88)->where('submission.status', 'returned'));
    Notification::assertSentTo($this->student, SubmissionReturned::class);
});

test('scores can not exceed the maximum points', function () {
    $this->actingAs($this->teacher)->put(route('submissions.grade', [$this->assignment, $this->student]), ['score' => 101])
        ->assertSessionHasErrors('score');
});

test('teachers see every student, including those who did not start', function () {
    $this->actingAs($this->teacher)->get(route('submissions.index', $this->assignment))
        ->assertInertia(fn (Assert $page) => $page
            ->component('items/Submissions')
            ->where('students.0.id', $this->student->id)
            ->where('students.0.status', 'assigned'),
        );

    $this->actingAs($this->student)->get(route('submissions.index', $this->assignment))->assertForbidden();
});

test('outsiders and teachers can not turn in work, and nobody sees another student\'s files', function () {
    $outsider = User::factory()->create();
    $classmate = User::factory()->create();
    $this->course->users()->attach($classmate, ['role' => 'student']);

    $this->actingAs($outsider)->post(route('submissions.turn-in', $this->assignment))->assertForbidden();
    $this->actingAs($this->teacher)->post(route('submissions.turn-in', $this->assignment))->assertForbidden();

    $this->actingAs($this->student)->post(route('submissions.files.store', $this->assignment), [
        'files' => [UploadedFile::fake()->create('jawaban.pdf', 20, 'application/pdf')],
    ]);
    $file = Submission::query()->sole()->attachments()->sole();

    $this->actingAs($classmate)->get(route('attachments.show', $file))->assertForbidden();
    $this->actingAs($classmate)->delete(route('submissions.files.destroy', $file))->assertForbidden();
    $this->actingAs($this->teacher)->get(route('attachments.show', $file))->assertOk();
});

test('the to-do page groups assignments by state', function () {
    $missing = Item::factory()->assignment()->create(['space_id' => $this->course->id, 'due_at' => now()->subDay(), 'title' => 'Terlambat']);
    $done = Item::factory()->assignment()->create(['space_id' => $this->course->id, 'title' => 'Selesai']);
    Item::factory()->assignment()->draft()->create(['space_id' => $this->course->id, 'title' => 'Draf']);

    $this->actingAs($this->student)->post(route('submissions.turn-in', $done));

    $this->actingAs($this->student)->get(route('todo'))->assertInertia(fn (Assert $page) => $page
        ->component('Todo')
        ->where('groups.assigned.0.id', $this->assignment->id)
        ->where('groups.missing.0.id', $missing->id)
        ->where('groups.done.0.id', $done->id)
        ->count('groups.assigned', 1),
    );

    $this->actingAs($this->teacher)->get(route('todo'))->assertInertia(fn (Assert $page) => $page
        ->where('toGrade.0.id', $done->id)
        ->where('toGrade.0.waiting', 1),
    );
});
