<?php

namespace App\Http\Controllers\Courses;

use App\Actions\Courses\JoinCourse;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JoinCourseController extends Controller
{
    /**
     * Join a class with its code. The route is rate limited so codes cannot be guessed.
     */
    public function __invoke(Request $request, JoinCourse $joinCourse): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::Student), 403);

        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);
        $course = $joinCourse->handle($request->user(), $data['code']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You joined :name.', ['name' => $course->name])]);

        return redirect()->route('courses.show', $course);
    }
}
