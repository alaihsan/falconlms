<?php

namespace App\Http\Controllers\Content;

use App\Actions\Content\SaveItem;
use App\Enums\ItemType;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\SaveItemRequest;
use App\Models\Folder;
use App\Models\Item;
use App\Models\Space;
use App\Models\Submission;
use App\Models\Topic;
use App\Models\User;
use App\Support\CreationContext;
use App\Support\Payload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    /**
     * The full-page editor for a new material, assignment or link.
     */
    public function create(Request $request, Space $space): Response
    {
        Gate::authorize('manage', $space);

        $data = $request->validate([
            'type' => ['required', Rule::enum(ItemType::class)->only(
                $space->isCourse() ? [ItemType::Material, ItemType::Assignment] : [ItemType::Material, ItemType::Assignment, ItemType::Link],
            )],
            'folder' => $space->isCourse() ? ['prohibited'] : ['nullable', 'integer', Rule::exists('folders', 'id')->where('space_id', $space->id)->whereNull('deleted_at')],
            'topic' => $space->isCourse() ? ['nullable', 'integer', Rule::exists('topics', 'id')->where('space_id', $space->id)] : ['prohibited'],
        ]);

        $folder = isset($data['folder']) ? Folder::query()->findOrFail((int) $data['folder']) : null;
        $topic = isset($data['topic']) ? Topic::query()->findOrFail((int) $data['topic']) : null;

        return Inertia::render('items/Edit', [
            'mode' => 'create',
            'type' => $data['type'],
            'space' => $this->spacePayload($space),
            'folder' => $folder ? Payload::folder($folder) : null,
            'topic' => $topic ? ['id' => $topic->id, 'name' => $topic->name] : null,
            'folderId' => $folder?->id,
            'topicId' => $topic?->id,
            'topics' => $this->topics($space),
            'item' => null,
            'context' => CreationContext::forPlace($space, $request->user(), $folder, $topic)->toArray(),
            'cancelUrl' => $space->isCourse()
                ? route('courses.classwork', $space)
                : route('library.show', $data['folder'] ?? null),
        ]);
    }

    public function store(SaveItemRequest $request, Space $space, SaveItem $saveItem): RedirectResponse
    {
        Gate::authorize('manage', $space);

        $data = $request->validated();
        $folder = isset($data['folder_id']) ? Folder::query()->findOrFail((int) $data['folder_id']) : null;
        $topic = isset($data['topic_id']) ? Topic::query()->findOrFail((int) $data['topic_id']) : null;
        $item = $saveItem->create($space, $request->user(), ItemType::from($data['type']), $data, $folder, $topic);
        $place = Payload::place($space, $folder, $topic);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $item->isPublished() || $space->isLibrary()
                ? __('Saved to :place.', ['place' => $place])
                : __('Saved as a draft in :place.', ['place' => $place]),
        ]);

        return $this->redirectToContainer($item);
    }

    public function show(Request $request, Item $item): Response
    {
        Gate::authorize('view', $item);

        /** @var User $user */
        $user = $request->user();
        $space = $item->space;
        $item->load(['attachments', 'creator', 'topic']);
        $canManage = $user->can('update', $item);
        $canSubmit = $user->can('submit', $item);

        $props = [
            'item' => Payload::item($item) + [
                'body' => $item->body,
                'maxPoints' => $item->max_points,
                'creator' => $item->creator?->name,
                'topic' => $item->topic?->name,
                'attachments' => $item->attachments->map->toPayload()->values(),
            ],
            'space' => $this->spacePayload($space),
            'breadcrumbs' => $this->breadcrumbs($item),
            'can' => ['update' => $canManage, 'submit' => $canSubmit],
            'context' => CreationContext::forItem($item, $user)->toArray(),
        ];

        if ($canSubmit) {
            $submission = Submission::query()
                ->with('attachments')
                ->where('item_id', $item->id)
                ->where('student_id', $user->id)
                ->first();

            $props['submission'] = $submission ? $this->studentSubmission($submission, $item) : null;
        }

        if ($canManage && $item->isAssignment() && $space->isCourse()) {
            $props['submissionStats'] = [
                'students' => $space->members()->where('role', 'student')->count(),
                'turnedIn' => $item->submissions()->whereIn('status', [SubmissionStatus::TurnedIn, SubmissionStatus::Returned])->count(),
            ];
        }

        return Inertia::render('items/Show', $props);
    }

    public function edit(Request $request, Item $item): Response
    {
        Gate::authorize('update', $item);

        $space = $item->space;
        $item->load('attachments');

        return Inertia::render('items/Edit', [
            'mode' => 'edit',
            'type' => $item->type->value,
            'space' => $this->spacePayload($space),
            'folderId' => $item->folder_id,
            'topicId' => $item->topic_id,
            'topics' => $this->topics($space),
            'item' => [
                'id' => $item->id,
                'title' => $item->title,
                'body' => $item->body,
                'url' => $item->url,
                'dueAt' => $item->due_at?->format('Y-m-d\TH:i'),
                'maxPoints' => $item->max_points,
                'isPublished' => $item->isPublished(),
                'attachments' => $item->attachments->map->toPayload()->values(),
            ],
            'cancelUrl' => route('items.show', $item),
            'context' => CreationContext::forItem($item, $request->user())->toArray(),
        ]);
    }

    public function update(SaveItemRequest $request, Item $item, SaveItem $saveItem): RedirectResponse
    {
        Gate::authorize('update', $item);

        $data = $request->validated();
        $topic = isset($data['topic_id']) ? Topic::query()->findOrFail((int) $data['topic_id']) : null;
        $saveItem->update($item, $request->user(), $data, $topic);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Saved.')]);

        return redirect()->route('items.show', $item);
    }

    public function publish(Item $item, SaveItem $saveItem): RedirectResponse
    {
        Gate::authorize('update', $item);

        $saveItem->publish($item);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Posted.')]);

        return back();
    }

    public function destroy(Item $item): RedirectResponse
    {
        Gate::authorize('delete', $item);

        $item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deleted.')]);

        return $this->redirectToContainer($item);
    }

    private function redirectToContainer(Item $item): RedirectResponse
    {
        return $item->space->isCourse()
            ? redirect()->route('courses.classwork', $item->space_id)
            : redirect()->route('library.show', $item->folder_id);
    }

    /**
     * @return array{id: int, kind: string, name: string}
     */
    private function spacePayload(Space $space): array
    {
        return ['id' => $space->id, 'kind' => $space->kind->value, 'name' => $space->displayName()];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function topics(Space $space): array
    {
        return $space->isCourse()
            ? $space->topics()->get(['id', 'name'])->map(fn (Topic $topic) => ['id' => $topic->id, 'name' => $topic->name])->values()->all()
            : [];
    }

    /**
     * @return list<array{title: string, href: string}>
     */
    private function breadcrumbs(Item $item): array
    {
        $space = $item->space;

        if ($space->isCourse()) {
            return [
                ['title' => $space->name, 'href' => route('courses.show', $space)],
                ['title' => __('Classwork'), 'href' => route('courses.classwork', $space)],
                ['title' => $item->title, 'href' => route('items.show', $item)],
            ];
        }

        $crumbs = [['title' => $space->displayName(), 'href' => route('library.show')]];

        if ($folder = $item->folder) {
            foreach ([...$folder->ancestors(), $folder] as $crumb) {
                $crumbs[] = ['title' => $crumb->name, 'href' => route('library.show', $crumb)];
            }
        }

        $crumbs[] = ['title' => $item->title, 'href' => route('items.show', $item)];

        return $crumbs;
    }

    /**
     * What a student sees of their own work. The score and feedback stay
     * hidden until the teacher returns it.
     *
     * @return array<string, mixed>
     */
    private function studentSubmission(Submission $submission, Item $item): array
    {
        $returned = $submission->status === SubmissionStatus::Returned;

        return [
            'status' => $submission->status->value,
            'body' => $submission->body,
            'turnedInAt' => $submission->turned_in_at?->toIso8601String(),
            'isLate' => $submission->isLate($item),
            'score' => $returned && $submission->score !== null ? (float) $submission->score : null,
            'feedback' => $returned ? $submission->feedback : null,
            'attachments' => $submission->attachments->map->toPayload()->values(),
        ];
    }
}
