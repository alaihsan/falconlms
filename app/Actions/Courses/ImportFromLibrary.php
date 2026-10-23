<?php

namespace App\Actions\Courses;

use App\Actions\Content\CopyItem;
use App\Models\Folder;
use App\Models\Item;
use App\Models\Space;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ImportFromLibrary
{
    public function __construct(private CopyItem $copyItem) {}

    /**
     * Copy library content into a class as drafts.
     *
     * Each chosen folder becomes a topic with the folder's name; items from
     * its sub-folders land in that same topic, so the class stays one level
     * deep. Chosen items go to the given topic.
     *
     * @param  list<int>  $folderIds
     * @param  list<int>  $itemIds
     */
    public function handle(Space $library, Space $course, User $user, array $folderIds, array $itemIds, ?Topic $topic): int
    {
        return DB::transaction(function () use ($library, $course, $user, $folderIds, $itemIds, $topic) {
            $copied = 0;

            $folders = Folder::query()->where('space_id', $library->id)->whereIn('id', $folderIds)->get();

            foreach ($folders as $folder) {
                $folderTopic = $course->topics()->firstOrCreate(
                    ['name' => $folder->name],
                    ['position' => (int) Topic::query()->where('space_id', $course->id)->max('position') + 1],
                );

                $subtree = Folder::query()
                    ->where('space_id', $library->id)
                    ->where('path', 'like', $folder->descendantPath().'%')
                    ->pluck('id')
                    ->push($folder->id);

                $items = Item::query()->with('attachments')->whereIn('folder_id', $subtree)->orderBy('id')->get();

                foreach ($items as $item) {
                    $this->copyItem->handle($item, $course, null, $folderTopic, $user, false);
                    $copied++;
                }
            }

            $items = Item::query()->with('attachments')->where('space_id', $library->id)->whereIn('id', $itemIds)->get();

            foreach ($items as $item) {
                $this->copyItem->handle($item, $course, null, $topic, $user, false);
                $copied++;
            }

            return $copied;
        });
    }
}
