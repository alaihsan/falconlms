<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    /**
     * A file can be opened by whoever can see what it is attached to.
     * Attachments of deleted items or submissions cannot be opened.
     */
    public function view(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        return $attachable !== null && $user->can('view', $attachable);
    }
}
