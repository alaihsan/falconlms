<?php

namespace App\Actions\Content;

use App\Models\Folder;
use App\Models\Item;
use Illuminate\Support\Facades\DB;

class DeleteFolder
{
    /**
     * Move a folder, its sub-folders and everything in them to the trash.
     */
    public function handle(Folder $folder): void
    {
        DB::transaction(function () use ($folder) {
            $folderIds = Folder::query()
                ->where('space_id', $folder->space_id)
                ->where('path', 'like', $folder->descendantPath().'%')
                ->pluck('id')
                ->push($folder->id);

            Item::query()->whereIn('folder_id', $folderIds)->delete();
            Folder::query()->whereIn('id', $folderIds)->delete();
        });
    }
}
