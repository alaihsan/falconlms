<?php

namespace App\Actions\Content;

use App\Enums\AttachmentKind;
use App\Enums\ItemType;
use App\Models\Attachment;
use App\Models\Folder;
use App\Models\Item;
use App\Models\Space;
use App\Models\Topic;
use App\Models\User;
use App\Support\Uploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CopyItem
{
    /**
     * Copy an item (with its files) into another space.
     *
     * Copies are independent: editing or deleting one never changes the
     * other. `source_item_id` remembers where the copy came from. A loose
     * file or link becomes a material with an attachment when it is copied
     * into a class, because classes only hold materials and assignments.
     */
    public function handle(Item $source, Space $target, ?Folder $folder, ?Topic $topic, User $user, bool $publish): Item
    {
        $type = $target->isCourse() && ! $source->type->allowedInCourse() ? ItemType::Material : $source->type;

        $copy = new Item([
            'title' => $source->title,
            'body' => $source->body,
            'url' => $type === ItemType::Link ? $source->url : null,
            'max_points' => $source->max_points,
        ]);

        $copy->forceFill([
            'space_id' => $target->id,
            'folder_id' => $target->isCourse() ? null : $folder?->id,
            'topic_id' => $target->isCourse() ? $topic?->id : null,
            'type' => $type,
            'position' => $target->isCourse() ? NextPosition::atTop($target, $topic?->id) : 0,
            'source_item_id' => $source->id,
            'created_by' => $user->id,
            'published_at' => $target->isCourse() && ! $publish ? null : now(),
        ])->save();

        if ($source->type === ItemType::Link && $type !== ItemType::Link && $source->url) {
            Uploads::attachLink($copy, $source->url, $user);
        }

        foreach ($source->attachments as $attachment) {
            $this->copyAttachment($attachment, $copy, $user);
        }

        return $copy;
    }

    private function copyAttachment(Attachment $attachment, Item $copy, User $user): void
    {
        if ($attachment->kind === AttachmentKind::Link) {
            Uploads::attachLink($copy, (string) $attachment->url, $user);

            return;
        }

        $disk = Storage::disk((string) $attachment->disk);
        $extension = pathinfo((string) $attachment->path, PATHINFO_EXTENSION);
        $path = 'attachments/'.now()->format('Y/m').'/'.Str::random(40).($extension ? '.'.$extension : '');

        $disk->copy((string) $attachment->path, $path);

        $replica = $attachment->replicate(['attachable_type', 'attachable_id', 'path', 'created_by']);
        $replica->forceFill([
            'attachable_type' => $copy->getMorphClass(),
            'attachable_id' => $copy->id,
            'path' => $path,
            'created_by' => $user->id,
        ])->save();
    }
}
