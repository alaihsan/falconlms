<?php

namespace App\Policies;

use App\Enums\MemberRole;
use App\Enums\Role;
use App\Enums\SpaceKind;
use App\Models\Space;
use App\Models\User;

class SpacePolicy
{
    /**
     * A personal library is private to its owner; a class is open to its members.
     */
    public function view(User $user, Space $space): bool
    {
        return match ($space->kind) {
            SpaceKind::PersonalLibrary => $space->owner_id === $user->id,
            SpaceKind::SchoolLibrary => $user->hasRole(Role::Teacher, Role::Admin),
            SpaceKind::Course => $user->hasRole(Role::Admin) || $space->roleOf($user) !== null,
        };
    }

    /**
     * Add, change and remove the content of a space.
     */
    public function manage(User $user, Space $space): bool
    {
        return match ($space->kind) {
            SpaceKind::PersonalLibrary => $space->owner_id === $user->id,
            SpaceKind::SchoolLibrary => $user->hasRole(Role::Admin),
            SpaceKind::Course => $user->hasRole(Role::Admin) || $space->roleOf($user) === MemberRole::Teacher,
        };
    }

    /**
     * Create a class.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(Role::Teacher, Role::Admin);
    }
}
