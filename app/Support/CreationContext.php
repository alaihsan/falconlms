<?php

namespace App\Support;

use App\Enums\NewMenuItem;
use App\Enums\Role;
use App\Models\User;

/**
 * Tells the "+ Baru" button where new content will be saved and which
 * entries to offer. Every content page passes one as its `context` prop,
 * so the frontend never has to guess the destination.
 */
final readonly class CreationContext
{
    /**
     * @param  list<NewMenuItem>  $allowed
     */
    public function __construct(
        public string $spaceKind,
        public string $spaceName,
        public array $allowed,
    ) {}

    /**
     * Content created from the home page goes to the teacher's own library.
     */
    public static function personalLibrary(User $user): self
    {
        return new self(
            spaceKind: 'personal_library',
            spaceName: __('My Library'),
            allowed: $user->hasRole(Role::Teacher) ? [
                NewMenuItem::Folder,
                NewMenuItem::Upload,
                NewMenuItem::UploadFolder,
                NewMenuItem::Material,
                NewMenuItem::Assignment,
                NewMenuItem::Quiz,
                NewMenuItem::Link,
            ] : [],
        );
    }

    /**
     * @return array{space: array{kind: string, name: string}, folder: null, topic: null, allowed: list<string>}
     */
    public function toArray(): array
    {
        return [
            'space' => ['kind' => $this->spaceKind, 'name' => $this->spaceName],
            'folder' => null,
            'topic' => null,
            'allowed' => array_map(fn (NewMenuItem $item) => $item->value, $this->allowed),
        ];
    }
}
