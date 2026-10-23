<?php

namespace App\Http\Controllers\Courses;

use App\Actions\Courses\ReorderClasswork;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\Item;
use App\Models\Space;
use App\Models\Topic;
use App\Models\User;
use App\Support\CreationContext;
use App\Support\Payload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ClassworkController extends Controller
{
    /**
     * The Classwork tab: every post on one page, grouped by topic.
     */
    public function index(Request $request, Space $course): Response
    {
        Gate::authorize('view', $course);

        /** @var User $user */
        $user = $request->user();
        $canManage = $user->can('manage', $course);

        $topics = $course->topics()->get();
        $filter = $request->integer('topik') ?: null;
        $filterTopic = $filter ? $topics->firstWhere('id', $filter) : null;

        $items = Item::query()
            ->where('space_id', $course->id)
            ->when(! $canManage, fn ($query) => $query->published())
            ->orderBy('position')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('courses/Classwork', [
            'course' => CourseController::coursePayload($course, $canManage),
            'context' => CreationContext::forCourse($course, $user, 'classwork', $filterTopic)->toArray(),
            'breadcrumbs' => [
                ['title' => $course->name, 'href' => route('courses.show', $course)],
                ['title' => __('Classwork'), 'href' => route('courses.classwork', $course)],
            ],
            'topics' => $topics->map(fn (Topic $topic) => ['id' => $topic->id, 'name' => $topic->name])->values(),
            'items' => $items->map(fn (Item $item) => Payload::item($item))->values(),
            'filterTopicId' => $filterTopic?->id,
            // Loaded only when the teacher opens the "From library" dialog.
            'library' => Inertia::optional(fn () => $this->libraryTree($user)),
        ]);
    }

    public function reorder(Request $request, Space $course, ReorderClasswork $reorder): RedirectResponse
    {
        Gate::authorize('manage', $course);

        $data = $request->validate([
            'topics' => ['array'],
            'topics.*' => ['integer'],
            'items' => ['array'],
            'items.*' => ['array'],
            'items.*.*' => ['integer'],
        ]);

        $reorder->handle($course, $data['topics'] ?? [], $data['items'] ?? []);

        return back();
    }

    /**
     * Folders and items of the teacher's own library for the import dialog.
     *
     * @return array{folders: array<int, array<string, mixed>>, items: array<int, array<string, mixed>>}
     */
    private function libraryTree(User $user): array
    {
        if (! $user->hasRole(Role::Teacher)) {
            return ['folders' => [], 'items' => []];
        }

        $library = Space::personalLibraryFor($user);

        return [
            'folders' => Folder::query()->where('space_id', $library->id)->orderBy('name')->get()
                ->map(fn (Folder $folder) => Payload::folder($folder) + ['parentId' => $folder->parent_id])
                ->values()->all(),
            'items' => Item::query()->where('space_id', $library->id)->orderBy('title')->get()
                ->map(fn (Item $item) => ['id' => $item->id, 'title' => $item->title, 'type' => $item->type->value, 'folderId' => $item->folder_id])
                ->values()->all(),
        ];
    }
}
