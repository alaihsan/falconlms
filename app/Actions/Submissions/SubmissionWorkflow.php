<?php

namespace App\Actions\Submissions;

use App\Enums\MemberRole;
use App\Enums\SubmissionStatus;
use App\Models\Item;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\SubmissionReturned;
use App\Notifications\SubmissionTurnedIn;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * The Google Classroom flow: the student attaches work and turns it in
 * (and may unsubmit to change it); the teacher scores it and returns it.
 */
class SubmissionWorkflow
{
    /**
     * The student's submission, started as a draft the first time.
     */
    public function forStudent(Item $assignment, User $student): Submission
    {
        $submission = Submission::query()
            ->where('item_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($submission) {
            return $submission;
        }

        $submission = new Submission;
        $submission->forceFill([
            'item_id' => $assignment->id,
            'student_id' => $student->id,
            'status' => SubmissionStatus::Draft,
        ])->save();

        return $submission;
    }

    public function ensureEditable(Submission $submission): void
    {
        if ($submission->isTurnedIn()) {
            throw ValidationException::withMessages([
                'submission' => __('Unsubmit your work before changing it.'),
            ]);
        }
    }

    public function turnIn(Item $assignment, User $student, ?string $body): Submission
    {
        $submission = $this->forStudent($assignment, $student);
        $this->ensureEditable($submission);

        $submission->forceFill([
            'status' => SubmissionStatus::TurnedIn,
            'body' => $body,
            'turned_in_at' => now(),
        ])->save();

        $teachers = $assignment->space->users()->wherePivot('role', MemberRole::Teacher->value)->get();
        Notification::send($teachers, new SubmissionTurnedIn($submission));

        return $submission;
    }

    public function unsubmit(Item $assignment, User $student): Submission
    {
        $submission = $this->forStudent($assignment, $student);

        if ($submission->status === SubmissionStatus::TurnedIn) {
            $submission->forceFill(['status' => SubmissionStatus::Draft, 'turned_in_at' => null])->save();
        }

        return $submission;
    }

    /**
     * Save a score and feedback; returning makes them visible to the student.
     */
    public function grade(Item $assignment, User $student, ?float $score, ?string $feedback, bool $return, User $teacher): Submission
    {
        $submission = $this->forStudent($assignment, $student);

        $submission->forceFill([
            'score' => $score,
            'feedback' => $feedback,
            'graded_by' => $teacher->id,
        ]);

        if ($return) {
            $submission->forceFill(['status' => SubmissionStatus::Returned, 'returned_at' => now()]);
        }

        $submission->save();

        if ($return) {
            $student->notify(new SubmissionReturned($submission));
        }

        return $submission;
    }
}
