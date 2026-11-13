<?php

namespace App\Http\Requests\Concerns;

use App\Enums\UserRole;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait ValidatesShareTargets
{
    /**
     * Rule limiting class targets to classes the current user teaches (admins may target any class).
     */
    protected function shareableClassRule(): Exists
    {
        $user = $this->user();
        $rule = Rule::exists('school_classes', 'id')->withoutTrashed();

        if ($user !== null && ! $user->isAdmin()) {
            $rule->where('guru_id', $user->id);
        }

        return $rule;
    }

    /**
     * Rule limiting teacher targets to other teachers.
     */
    protected function shareableTeacherRule(): Exists
    {
        return Rule::exists('users', 'id')
            ->where('role', UserRole::Guru->value)
            ->whereNot('id', $this->user()?->id);
    }

    /**
     * Rule limiting folder targets to folders owned by the current user.
     */
    protected function ownedFolderRule(): Exists
    {
        return Rule::exists('folders', 'id')->where('user_id', $this->user()?->id);
    }
}
