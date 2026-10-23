<?php

namespace App\Notifications;

use App\Models\Submission;

class SubmissionTurnedIn extends InAppNotification
{
    public function __construct(public Submission $submission) {}

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->submission->item->title,
            'body' => __(':name turned in their work.', ['name' => $this->submission->student->name]),
            'url' => route('submissions.index', $this->submission->item_id),
        ];
    }
}
