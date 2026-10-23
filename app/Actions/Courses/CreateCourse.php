<?php

namespace App\Actions\Courses;

use App\Enums\MemberRole;
use App\Enums\SpaceKind;
use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateCourse
{
    /**
     * Create a class; its creator becomes a teacher of it.
     *
     * @param  array{name: string, section?: string|null, subject?: string|null}  $data
     */
    public function handle(User $user, array $data): Space
    {
        return DB::transaction(function () use ($user, $data) {
            $course = new Space($data);
            $course->forceFill([
                'kind' => SpaceKind::Course,
                'owner_id' => $user->id,
                'join_code' => Space::generateJoinCode(),
            ])->save();

            $course->users()->attach($user, ['role' => MemberRole::Teacher]);

            return $course;
        });
    }
}
