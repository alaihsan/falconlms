<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * Only the student and the class's teachers can see a submission.
     */
    public function view(User $user, Submission $submission): bool
    {
        return $submission->student_id === $user->id || $this->grade($user, $submission);
    }

    public function grade(User $user, Submission $submission): bool
    {
        $item = $submission->item;

        return $item !== null && $user->can('manage', $item->space);
    }
}
