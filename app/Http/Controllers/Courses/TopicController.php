<?php

namespace App\Http\Controllers\Courses;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TopicController extends Controller
{
    public function store(Request $request, Space $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('topics')->where('space_id', $course->id)],
        ]);

        $topic = $course->topics()->create([
            'name' => $data['name'],
            'position' => (int) Topic::query()->where('space_id', $course->id)->max('position') + 1,
        ]);

        return redirect()->route('courses.classwork', ['course' => $course, 'topik' => $topic->id]);
    }

    public function update(Request $request, Topic $topic): RedirectResponse
    {
        Gate::authorize('manage', $topic->space);

        $topic->update($request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('topics')->where('space_id', $topic->space_id)->ignore($topic)],
        ]));

        return back();
    }

    /**
     * Posts in a deleted topic are kept and shown without a topic.
     */
    public function destroy(Topic $topic): RedirectResponse
    {
        Gate::authorize('manage', $topic->space);

        $topic->delete();

        return redirect()->route('courses.classwork', $topic->space_id);
    }
}
