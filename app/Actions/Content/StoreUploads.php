<?php

namespace App\Actions\Content;

use App\Enums\ItemType;
use App\Models\Folder;
use App\Models\Item;
use App\Models\Space;
use App\Models\Topic;
use App\Models\User;
use App\Support\Uploads;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StoreUploads
{
    public function __construct(private CreateFolder $createFolder) {}

    /**
     * Save uploaded files where the user is.
     *
     * In a library each file becomes a file item; files from an uploaded
     * folder recreate its sub-folders. In a class each file becomes a draft
     * material in the topic, so the teacher can add a description first.
     *
     * @param  list<UploadedFile>  $files
     * @param  list<string|null>  $relativePaths  Paths from a folder upload, e.g. "Bab 1/video.mp4"
     */
    public function handle(Space $space, ?Folder $folder, ?Topic $topic, array $files, array $relativePaths, User $user): int
    {
        return DB::transaction(function () use ($space, $folder, $topic, $files, $relativePaths, $user) {
            $folders = [];

            foreach ($files as $index => $file) {
                $name = Uploads::cleanName($file->getClientOriginalName());
                $item = new Item(['title' => $space->isCourse() ? pathinfo($name, PATHINFO_FILENAME) : $name]);

                if ($space->isCourse()) {
                    $item->forceFill([
                        'type' => ItemType::Material,
                        'topic_id' => $topic?->id,
                        'position' => NextPosition::atTop($space, $topic?->id),
                        'published_at' => null,
                    ]);
                } else {
                    $target = $this->folderFor($space, $folder, $relativePaths[$index] ?? null, $user, $folders);
                    $item->forceFill([
                        'type' => ItemType::File,
                        'folder_id' => $target?->id,
                        'published_at' => now(),
                    ]);
                }

                $item->forceFill(['space_id' => $space->id, 'created_by' => $user->id])->save();
                Uploads::attach($item, $file, $user);
            }

            return count($files);
        });
    }

    /**
     * Find or create the sub-folders named in a relative upload path.
     *
     * @param  array<string, Folder>  $cache
     */
    private function folderFor(Space $library, ?Folder $base, ?string $relativePath, User $user, array &$cache): ?Folder
    {
        $segments = $this->safeSegments($relativePath);
        $current = $base;
        $key = '';

        foreach ($segments as $segment) {
            $key .= '/'.$segment;

            $current = $cache[$key] ??= Folder::query()
                ->where('space_id', $library->id)
                ->where('parent_id', $current?->id)
                ->where('name', $segment)
                ->first()
                ?? $this->createFolder->handle($library, $current, $segment, $user);
        }

        return $current;
    }

    /**
     * Folder names from a browser-supplied path, without the file name and
     * without anything that could climb out of the target folder.
     *
     * @return list<string>
     */
    private function safeSegments(?string $relativePath): array
    {
        if ($relativePath === null || $relativePath === '') {
            return [];
        }

        $parts = explode('/', str_replace('\\', '/', $relativePath));
        array_pop($parts);

        $segments = [];

        foreach ($parts as $part) {
            $part = trim((string) preg_replace('/[\x00-\x1F\x7F]/u', '', $part));

            if ($part !== '' && $part !== '.' && $part !== '..') {
                $segments[] = mb_substr($part, 0, 100);
            }
        }

        return array_slice($segments, 0, 10);
    }
}
