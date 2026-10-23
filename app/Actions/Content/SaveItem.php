<?php

namespace App\Actions\Content;

use App\Enums\ItemType;
use App\Models\Folder;
use App\Models\Item;
use App\Models\Space;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\ClassworkPosted;
use App\Support\RichText;
use App\Support\Uploads;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Creates and updates materials, assignments and links from the editor.
 *
 * $data is the validated request (see SaveItemRequest): title, body, url,
 * due_at, max_points, publish, files, links, remove_attachments and
 * also_save_to_library.
 */
class SaveItem
{
    public function __construct(private CopyItem $copyItem) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Space $space, User $user, ItemType $type, array $data, ?Folder $folder, ?Topic $topic): Item
    {
        $item = DB::transaction(function () use ($space, $user, $type, $data, $folder, $topic) {
            $item = new Item($this->attributes($type, $data));
            $item->forceFill([
                'space_id' => $space->id,
                'folder_id' => $space->isCourse() ? null : $folder?->id,
                'topic_id' => $space->isCourse() ? $topic?->id : null,
                'type' => $type,
                'position' => $space->isCourse() ? NextPosition::atTop($space, $topic?->id) : 0,
                'created_by' => $user->id,
                // Library content is always "published" to its owner.
                'published_at' => $space->isCourse() && empty($data['publish']) ? null : now(),
            ])->save();

            $this->attach($item, $data, $user);

            if ($space->isCourse() && ! empty($data['also_save_to_library'])) {
                $this->copyItem->handle($item->load('attachments'), Space::personalLibraryFor($user), null, null, $user, true);
            }

            return $item;
        });

        if ($space->isCourse() && $item->isPublished()) {
            $this->notifyStudents($space, $item);
        }

        return $item;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Item $item, User $user, array $data, ?Topic $topic): Item
    {
        $space = $item->space;
        $wasPublished = $item->isPublished();

        DB::transaction(function () use ($item, $space, $user, $data, $topic) {
            $item->fill($this->attributes($item->type, $data));

            if ($space->isCourse()) {
                if ($item->topic_id !== $topic?->id) {
                    $item->forceFill(['topic_id' => $topic?->id, 'position' => NextPosition::atTop($space, $topic?->id)]);
                }

                if (! empty($data['publish']) && ! $item->isPublished()) {
                    $item->forceFill(['published_at' => now()]);
                }
            }

            $item->save();

            if (! empty($data['remove_attachments']) && is_array($data['remove_attachments'])) {
                // Only attachments of this item; each delete also removes the file from disk.
                $item->attachments()->whereIn('id', $data['remove_attachments'])->get()->each->delete();
            }

            $this->attach($item, $data, $user);
        });

        if ($space->isCourse() && ! $wasPublished && $item->isPublished()) {
            $this->notifyStudents($space, $item);
        }

        return $item;
    }

    public function publish(Item $item): void
    {
        if ($item->isPublished()) {
            return;
        }

        $item->forceFill(['published_at' => now()])->save();
        $this->notifyStudents($item->space, $item);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(ItemType $type, array $data): array
    {
        $string = fn (string $key): ?string => isset($data[$key]) && is_scalar($data[$key]) ? (string) $data[$key] : null;

        return [
            'title' => (string) $string('title'),
            'body' => RichText::sanitize($string('body')),
            'url' => $type === ItemType::Link ? $string('url') : null,
            'due_at' => $type === ItemType::Assignment && $string('due_at') ? Carbon::parse((string) $string('due_at')) : null,
            'max_points' => $type === ItemType::Assignment && $string('max_points') !== null ? (int) $string('max_points') : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function attach(Item $item, array $data, User $user): void
    {
        foreach ((array) ($data['files'] ?? []) as $file) {
            if ($file instanceof UploadedFile) {
                Uploads::attach($item, $file, $user);
            }
        }

        foreach ((array) ($data['links'] ?? []) as $link) {
            Uploads::attachLink($item, (string) $link, $user);
        }
    }

    private function notifyStudents(Space $space, Item $item): void
    {
        $students = $space->users()->wherePivot('role', 'student')->get();

        Notification::send($students, new ClassworkPosted($item));
    }
}
