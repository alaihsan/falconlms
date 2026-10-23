<?php

namespace App\Actions\Content;

use App\Models\Item;
use App\Models\Space;

/**
 * New posts go to the top of their topic, as in Google Classroom.
 */
final class NextPosition
{
    public static function atTop(Space $space, ?int $topicId): int
    {
        Item::query()
            ->where('space_id', $space->id)
            ->where('topic_id', $topicId)
            ->increment('position');

        return 0;
    }
}
