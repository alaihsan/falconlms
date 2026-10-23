<?php

namespace App\Actions\Courses;

use App\Enums\MemberRole;
use App\Models\Space;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class JoinCourse
{
    /**
     * Join a class with the code the teacher shared.
     */
    public function handle(User $student, string $code): Space
    {
        $course = Space::query()
            ->courses()
            ->whereNull('archived_at')
            ->where('join_code', strtolower(trim($code)))
            ->first();

        if (! $course) {
            throw ValidationException::withMessages(['code' => __('No class uses that code.')]);
        }

        $course->users()->syncWithoutDetaching([$student->id => ['role' => MemberRole::Student]]);

        return $course;
    }
}
