<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Falcon only notifies inside the app (the bell icon): the school intranet
 * has no mail server. Notifications are queued so a class of 36 students
 * does not slow down the teacher's request.
 */
abstract class InAppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    abstract public function toArray(object $notifiable): array;
}
