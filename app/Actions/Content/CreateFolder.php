<?php

namespace App\Actions\Content;

use App\Models\Folder;
use App\Models\Space;
use App\Models\User;

class CreateFolder
{
    public function handle(Space $library, ?Folder $parent, string $name, User $user): Folder
    {
        $folder = new Folder(['name' => $name]);
        $folder->forceFill([
            'space_id' => $library->id,
            'parent_id' => $parent?->id,
            'path' => $parent ? $parent->descendantPath() : '/',
            'created_by' => $user->id,
        ])->save();

        return $folder;
    }
}
