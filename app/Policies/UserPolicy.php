<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole(Role::Admin);
    }

    /**
     * Admins cannot change their own role, so a school never locks itself out.
     */
    public function changeRole(User $user, User $model): bool
    {
        return $user->hasRole(Role::Admin) && $user->id !== $model->id;
    }
}
