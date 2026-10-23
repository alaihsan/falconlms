<?php

namespace App\Http\Controllers\Courses;

use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Space;
use App\Notifications\AnnouncementPosted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class PostController extends Controller
{
    /**
     * Post an announcement on the Forum. The text is shown as plain text.
     */
    public function store(Request $request, Space $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $post = new Post($request->validate(['body' => ['required', 'string', 'max:5000']]));
        $post->forceFill(['space_id' => $course->id, 'author_id' => $request->user()->id])->save();

        Notification::send(
            $course->users()->wherePivot('role', MemberRole::Student->value)->get(),
            new AnnouncementPosted($post),
        );

        return redirect()->route('courses.show', $course);
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        abort_unless($post->author_id === $request->user()->id || $request->user()->can('manage', $post->space), 403);

        $post->delete();

        return back();
    }
}
