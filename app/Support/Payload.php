<?php

namespace App\Support;

use App\Enums\ItemType;
use App\Models\Folder;
use App\Models\Item;
use App\Models\Space;

/**
 * Shapes models into the small arrays the pages receive, so only fields
 * that are meant for the browser leave the server.
 */
final class Payload
{
    /**
     * @return array{id: int, name: string}
     */
    public static function folder(Folder $folder): array
    {
        return ['id' => $folder->id, 'name' => $folder->name];
    }

    /**
     * An item in a list. Expects `attachments` to be loaded for file items.
     *
     * @return array<string, mixed>
     */
    public static function item(Item $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type->value,
            'title' => $item->title,
            'topicId' => $item->topic_id,
            'folderId' => $item->folder_id,
            'isPublished' => $item->isPublished(),
            'publishedAt' => $item->published_at?->toIso8601String(),
            'createdAt' => $item->created_at?->toIso8601String(),
            'dueAt' => $item->due_at?->toIso8601String(),
            'url' => $item->type === ItemType::Link ? $item->url : null,
            'file' => $item->type === ItemType::File && $item->relationLoaded('attachments')
                ? $item->attachments->first()?->toPayload()
                : null,
        ];
    }

    /**
     * @return array{id: int, name: string, section: string|null, subject: string|null}
     */
    public static function course(Space $course): array
    {
        return [
            'id' => $course->id,
            'name' => $course->name,
            'section' => $course->section,
            'subject' => $course->subject,
        ];
    }
}
