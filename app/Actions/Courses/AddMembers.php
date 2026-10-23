<?php

namespace App\Actions\Courses;

use App\Enums\MemberRole;
use App\Enums\Role;
use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Str;

class AddMembers
{
    /**
     * Add accounts to a class by username. Students join as students,
     * teachers as co-teachers.
     *
     * @return array{added: list<string>, existing: list<string>, unknown: list<string>}
     */
    public function handle(Space $course, string $usernames): array
    {
        $names = collect(preg_split('/[\s,;]+/', Str::lower($usernames)) ?: [])
            ->filter()
            ->unique()
            ->take(200)
            ->values();

        $users = User::query()->whereIn('username', $names)->get()->keyBy('username');
        $memberIds = $course->members()->pluck('user_id')->all();

        $result = ['added' => [], 'existing' => [], 'unknown' => []];

        foreach ($names as $name) {
            $user = $users->get($name);

            if (! $user || $user->hasRole(Role::Admin, Role::Guardian)) {
                $result['unknown'][] = $name;
            } elseif (in_array($user->id, $memberIds, true)) {
                $result['existing'][] = $name;
            } else {
                $role = $user->hasRole(Role::Teacher) ? MemberRole::Teacher : MemberRole::Student;
                $course->users()->attach($user, ['role' => $role]);
                $result['added'][] = $name;
            }
        }

        return $result;
    }
}
