<?php

namespace App\Support;

use App\Enums\NewMenuItem;
use App\Enums\Role;
use App\Models\Folder;
use App\Models\Item;
use App\Models\Space;
use App\Models\Topic;
use App\Models\User;

/**
 * Tells the "+ Baru" button where new content will be saved and which
 * entries to offer. Pages pass one as their `context` prop; every other
 * page gets the home context (shared by HandleInertiaRequests), so the
 * button is always there and the frontend never guesses the destination.
 */
final readonly class CreationContext
{
    private const array LIBRARY_ITEMS = [
        NewMenuItem::Folder,
        NewMenuItem::Upload,
        NewMenuItem::UploadFolder,
        NewMenuItem::Material,
        NewMenuItem::Assignment,
        NewMenuItem::Link,
    ];

    /**
     * @param  list<NewMenuItem>  $allowed
     */
    public function __construct(
        public array $allowed,
        public ?Space $space = null,
        public ?Folder $folder = null,
        public ?Topic $topic = null,
        public bool $canAlsoSaveToLibrary = false,
    ) {}

    /**
     * Teachers save to the root of their library from the home page;
     * students can join a class; admins manage the school.
     */
    public static function forHome(User $user): self
    {
        return match ($user->role) {
            Role::Teacher => new self(
                [...self::LIBRARY_ITEMS, NewMenuItem::Course],
                Space::personalLibraryFor($user),
            ),
            Role::Admin => new self([NewMenuItem::Course, NewMenuItem::User, NewMenuItem::ImportUsers]),
            Role::Student => new self([NewMenuItem::JoinCourse]),
            Role::Guardian => new self([]),
        };
    }

    public static function forLibrary(Space $library, ?Folder $folder, User $user): self
    {
        return $user->can('manage', $library)
            ? new self(self::LIBRARY_ITEMS, $library, $folder)
            : self::forHome($user);
    }

    /**
     * Users who cannot add content to the class (students) get what they
     * can do from home instead, such as joining another class.
     *
     * @param  'stream'|'classwork'|'members'|'item'  $page
     */
    public static function forCourse(Space $course, User $user, string $page, ?Topic $topic = null): self
    {
        if (! $user->can('manage', $course)) {
            return self::forHome($user);
        }

        $allowed = match ($page) {
            'stream' => [NewMenuItem::Announcement, NewMenuItem::Material, NewMenuItem::Assignment],
            'classwork' => [NewMenuItem::Material, NewMenuItem::Assignment, NewMenuItem::Topic, NewMenuItem::FromLibrary],
            'members' => [NewMenuItem::AddMembers],
            'item' => [NewMenuItem::Material, NewMenuItem::Assignment],
        };

        return new self(
            $allowed,
            $course,
            topic: $topic,
            // Only teachers have a personal library to copy into.
            canAlsoSaveToLibrary: $page !== 'members' && $user->hasRole(Role::Teacher),
        );
    }

    /**
     * A page about one place (a folder or a topic) saves next to it.
     */
    public static function forPlace(Space $space, User $user, ?Folder $folder = null, ?Topic $topic = null): self
    {
        return $space->isCourse()
            ? self::forCourse($space, $user, 'item', $topic)
            : self::forLibrary($space, $folder, $user);
    }

    /**
     * A content page saves next to the item: its folder or its topic.
     */
    public static function forItem(Item $item, User $user): self
    {
        return self::forPlace($item->space, $user, $item->folder, $item->topic);
    }

    public static function forAdminUsers(): self
    {
        return new self([NewMenuItem::User, NewMenuItem::ImportUsers]);
    }

    /**
     * @return array{space: array{id: int, kind: string, name: string}|null, folder: array{id: int, name: string}|null, topic: array{id: int, name: string}|null, allowed: list<string>, canAlsoSaveToLibrary: bool}
     */
    public function toArray(): array
    {
        return [
            'space' => $this->space ? [
                'id' => $this->space->id,
                'kind' => $this->space->kind->value,
                'name' => $this->space->displayName(),
            ] : null,
            'folder' => $this->folder ? ['id' => $this->folder->id, 'name' => $this->folder->name] : null,
            'topic' => $this->topic ? ['id' => $this->topic->id, 'name' => $this->topic->name] : null,
            'allowed' => array_map(fn (NewMenuItem $item) => $item->value, $this->allowed),
            'canAlsoSaveToLibrary' => $this->canAlsoSaveToLibrary,
        ];
    }
}
