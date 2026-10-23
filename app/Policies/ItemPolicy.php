<?php

namespace App\Policies;

use App\Enums\MemberRole;
use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    /**
     * Students only see published items; drafts are visible to the teachers.
     */
    public function view(User $user, Item $item): bool
    {
        $space = $item->space;

        return $user->can('view', $space) && ($item->isPublished() || $user->can('manage', $space));
    }

    public function update(User $user, Item $item): bool
    {
        return $user->can('manage', $item->space);
    }

    public function delete(User $user, Item $item): bool
    {
        return $user->can('manage', $item->space);
    }

    /**
     * Work on and turn in a published assignment of a class the user attends.
     */
    public function submit(User $user, Item $item): bool
    {
        $space = $item->space;

        return $item->isAssignment()
            && $item->isPublished()
            && $space->isCourse()
            && $space->roleOf($user) === MemberRole::Student;
    }
}
