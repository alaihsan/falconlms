<?php

namespace App\Http\Controllers\Courses;

use App\Actions\Courses\CreateCourse;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Post;
use App\Models\Space;
use App\Models\User;
use App\Support\CreationContext;
use App\Support\Payload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function store(Request $request, CreateCourse $createCourse): RedirectResponse
    {
        Gate::authorize('create', Space::class);

        $course = $createCourse->handle($request->user(), $this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Class created.')]);

        return redirect()->route('courses.show', $course);
    }

    /**
     * The Forum tab: announcements and newly posted classwork, newest first.
     */
    public function show(Request $request, Space $course): Response
    {
        Gate::authorize('view', $course);

        /** @var User $user */
        $user = $request->user();
        $canManage = $user->can('manage', $course);

        $posts = Post::query()->with('author')->where('space_id', $course->id)->latest()->limit(30)->get()
            ->map(fn (Post $post) => [
                'kind' => 'post',
                'id' => $post->id,
                'body' => $post->body,
                'author' => $post->author?->name,
                'date' => $post->created_at?->toIso8601String(),
                'canDelete' => $canManage || $post->author_id === $user->id,
            ]);

        $items = Item::query()->with('creator')->where('space_id', $course->id)->published()->latest('published_at')->limit(30)->get()
            ->map(fn (Item $item) => [
                'kind' => 'item',
                'id' => $item->id,
                'type' => $item->type->value,
                'title' => $item->title,
                'author' => $item->creator?->name,
                'date' => $item->published_at?->toIso8601String(),
            ]);

        return Inertia::render('courses/Stream', [
            'course' => $this->coursePayload($course, $canManage),
            'context' => CreationContext::forCourse($course, $user, 'stream')->toArray(),
            'breadcrumbs' => [['title' => $course->name, 'href' => route('courses.show', $course)]],
            'feed' => $posts->concat($items)->sortByDesc('date')->take(30)->values(),
            'upcoming' => Item::query()
                ->where('space_id', $course->id)
                ->published()
                ->where('type', 'assignment')
                ->where('due_at', '>=', now())
                ->orderBy('due_at')
                ->limit(5)
                ->get()
                ->map(fn (Item $item) => Payload::item($item)),
        ]);
    }

    public function update(Request $request, Space $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $course->update($this->validated($request));

        return back();
    }

    /**
     * Give the class a new code, e.g. when the old one was shared too widely.
     */
    public function regenerateCode(Space $course): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $course->forceFill(['join_code' => Space::generateJoinCode()])->save();

        return back();
    }

    /**
     * @return array{name: string, section: string|null, subject: string|null}
     */
    private function validated(Request $request): array
    {
        /** @var array{name: string, section: string|null, subject: string|null} */
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'section' => ['nullable', 'string', 'max:100'],
            'subject' => ['nullable', 'string', 'max:100'],
        ]);
    }

    /**
     * The class header shared by the Forum, Classwork and Members tabs.
     *
     * @return array<string, mixed>
     */
    public static function coursePayload(Space $course, bool $canManage): array
    {
        return Payload::course($course) + [
            'joinCode' => $canManage ? $course->join_code : null,
            'canManage' => $canManage,
        ];
    }
}
