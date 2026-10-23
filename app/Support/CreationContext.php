<?php

namespace App\Support;

use App\Enums\NewMenuItem;
use App\Enums\Role;
use App\Models\Folder;
use App\Models\Space;
use App\Models\Topic;
use App\Models\User;

/**
 * Tells the "+ Baru" button where new content will be saved and which
 * entries to offer. Every page passes one as its `context` prop, so the
 * frontend never has to guess the destination.
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
            : new self([]);
    }

    /**
     * @param  'stream'|'classwork'|'members'  $page
     */
    public static function forCourse(Space $course, User $user, string $page, ?Topic $topic = null): self
    {
        if (! $user->can('manage', $course)) {
            return new self([]);
        }

        $allowed = match ($page) {
            'stream' => [NewMenuItem::Announcement, NewMenuItem::Material, NewMenuItem::Assignment],
            'classwork' => [NewMenuItem::Material, NewMenuItem::Assignment, NewMenuItem::Topic, NewMenuItem::FromLibrary],
            'members' => [NewMenuItem::AddMembers],
        };

        return new self(
            $allowed,
            $course,
            topic: $topic,
            // Only teachers have a personal library to copy into.
            canAlsoSaveToLibrary: $page !== 'members' && $user->hasRole(Role::Teacher),
        );
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
