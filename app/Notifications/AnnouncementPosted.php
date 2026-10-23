<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Support\Str;

class AnnouncementPosted extends InAppNotification
{
    public function __construct(public Post $post) {}

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->post->space->name,
            'body' => __('Announcement: :text', ['text' => Str::limit($this->post->body, 80)]),
            'url' => route('courses.show', $this->post->space_id),
        ];
    }
}
