<?php

namespace App\Notifications;

use App\Models\Submission;

class SubmissionReturned extends InAppNotification
{
    public function __construct(public Submission $submission) {}

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->submission->item->title,
            'body' => $this->submission->score !== null
                ? __('Your work was returned with a score of :score.', ['score' => (float) $this->submission->score])
                : __('Your work was returned.'),
            'url' => route('items.show', $this->submission->item_id),
        ];
    }
}
