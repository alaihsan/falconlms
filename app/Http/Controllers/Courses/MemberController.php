<?php

namespace App\Http\Controllers\Courses;

use App\Actions\Courses\AddMembers;
use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\SpaceMember;
use App\Models\User;
use App\Support\CreationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * The Members tab (Anggota).
     */
    public function index(Request $request, Space $course): Response
    {
        Gate::authorize('view', $course);

        /** @var User $user */
        $user = $request->user();
        $canManage = $user->can('manage', $course);
        $members = $course->members()->with('user')->get()->sortBy(fn (SpaceMember $member) => $member->user->name);

        $present = fn (SpaceMember $member) => [
            'id' => $member->user->id,
            'name' => $member->user->name,
            'username' => $canManage ? $member->user->username : null,
        ];

        return Inertia::render('courses/Members', [
            'course' => CourseController::coursePayload($course, $canManage),
            'context' => CreationContext::forCourse($course, $user, 'members')->toArray(),
            'breadcrumbs' => [
                ['title' => $course->name, 'href' => route('courses.show', $course)],
                ['title' => __('Members'), 'href' => route('courses.members', $course)],
            ],
            'teachers' => $members->filter(fn (SpaceMember $member) => $member->role === MemberRole::Teacher)->map($present)->values(),
            'students' => $members->filter(fn (SpaceMember $member) => $member->role === MemberRole::Student)->map($present)->values(),
        ]);
    }

    public function store(Request $request, Space $course, AddMembers $addMembers): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $data = $request->validate(['usernames' => ['required', 'string', 'max:10000']]);
        $result = $addMembers->handle($course, $data['usernames']);

        $message = trans_choice(':count account added.|:count accounts added.', count($result['added']));

        if ($result['unknown']) {
            $message .= ' '.__('Not found: :names.', ['names' => implode(', ', $result['unknown'])]);
        }

        Inertia::flash('toast', ['type' => $result['unknown'] ? 'warning' : 'success', 'message' => $message]);

        return back();
    }

    public function destroy(Space $course, User $user): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $teachers = $course->members()->where('role', MemberRole::Teacher)->count();
        $isTeacher = $course->roleOf($user) === MemberRole::Teacher;

        // A class always keeps at least one teacher.
        abort_if($isTeacher && $teachers <= 1, 422, __('A class needs at least one teacher.'));

        $course->users()->detach($user);

        return back();
    }
}
