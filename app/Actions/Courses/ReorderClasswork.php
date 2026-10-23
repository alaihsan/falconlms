<?php

namespace App\Actions\Courses;

use App\Models\Item;
use App\Models\Space;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;

class ReorderClasswork
{
    /**
     * Save the order of topics and of the posts within each topic after
     * the teacher drags them around. Ids from other classes are ignored.
     *
     * @param  array<int, int>  $topicIds
     * @param  array<int|string, array<int, int>>  $itemsByTopic  Topic id (or "none") => item ids in order
     */
    public function handle(Space $course, array $topicIds, array $itemsByTopic): void
    {
        DB::transaction(function () use ($course, $topicIds, $itemsByTopic) {
            $validTopics = $course->topics()->pluck('id')->all();

            foreach (array_values($topicIds) as $position => $topicId) {
                if (in_array($topicId, $validTopics, true)) {
                    Topic::query()->whereKey($topicId)->update(['position' => $position]);
                }
            }

            foreach ($itemsByTopic as $topicKey => $itemIds) {
                $topicId = $topicKey === 'none' ? null : (int) $topicKey;

                if ($topicId !== null && ! in_array($topicId, $validTopics, true)) {
                    continue;
                }

                foreach (array_values($itemIds) as $position => $itemId) {
                    Item::query()
                        ->whereKey($itemId)
                        ->where('space_id', $course->id)
                        ->update(['topic_id' => $topicId, 'position' => $position]);
                }
            }
        });
    }
}
