<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\MoveLearningItemRequest;
use App\Http\Requests\ShareLearningItemRequest;
use App\Http\Requests\StoreLearningItemRequest;
use App\Models\Folder;
use App\Models\LearningItem;
use App\Models\PollOption;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\DocumentPreviewService;
use App\Services\H5pPackageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LibraryController extends Controller
{
    public function __construct(
        private DocumentPreviewService $previews,
        private H5pPackageService $h5pPackages,
    ) {}

    /**
     * Display the library: the user's own folders and items, items shared with them, or class items.
     */
    public function index(Request $request): InertiaResponse
    {
        /** @var User $user */
        $user = $request->user();
        $search = $request->string('search')->trim()->value();
        $type = $request->string('type')->trim()->value();
        $classId = $request->integer('class_id');
        $view = $this->resolveView($user, $request->string('view')->value());
        $isFiltering = $search !== '' || $type !== '' || $classId > 0;

        $currentFolder = null;
        if ($view === 'mine' && $request->integer('folder') > 0) {
            $currentFolder = $user->folders()->findOrFail($request->integer('folder'));
        }

        $query = LearningItem::query()
            ->visibleTo($user)
            ->with([
                'classes:id,name,subject',
                'author:id,name',
                'sharedWith:id,name',
                'pollOptions' => fn ($options) => $options->withCount('votes'),
                'pollVotes' => fn ($votes) => $votes->where('user_id', $user->id),
            ])
            ->when($search !== '', function (Builder $q) use ($search): void {
                $q->where(function (Builder $sub) use ($search): void {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('topic', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when($type !== '', fn (Builder $q) => $q->where('type', $type))
            ->when($classId > 0, fn (Builder $q) => $q->whereHas(
                'classes',
                fn (Builder $classes) => $classes->where('school_classes.id', $classId),
            ));

        if ($view === 'mine') {
            $query->where('user_id', $user->id);

            if (! $isFiltering) {
                $query->where('folder_id', $currentFolder?->id);
            }
        } elseif ($view === 'shared') {
            $query->where('user_id', '!=', $user->id);
        }

        $items = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (LearningItem $item): array => $this->presentItem($item, $user));

        $ownFolders = $user->isSiswa() ? new Collection : $user->folders()->orderBy('name')->get();

        $folders = ($view === 'mine' && ! $isFiltering)
            ? $ownFolders
                ->where('parent_id', $currentFolder?->id)
                ->values()
                ->map(fn (Folder $folder): array => ['id' => $folder->id, 'name' => $folder->name])
                ->all()
            : [];

        return Inertia::render('Library/Index', [
            'items' => $items,
            'folders' => $folders,
            'currentFolder' => $currentFolder ? ['id' => $currentFolder->id, 'name' => $currentFolder->name] : null,
            'breadcrumbs' => $currentFolder ? $currentFolder->breadcrumbs() : [],
            'folderOptions' => $this->folderOptions($ownFolders),
            'classes' => $this->getAvailableClasses($user),
            'teachers' => $this->getShareableTeachers($user),
            'view' => $view,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'class_id' => $classId ?: null,
            ],
            'upload' => [
                'extensions' => LearningItem::ATTACHMENT_EXTENSIONS,
                'max_megabytes' => intdiv(LearningItem::ATTACHMENT_MAX_KILOBYTES, 1024),
            ],
        ]);
    }

    /**
     * Store a new learning item, optionally with an uploaded file, and share it to the chosen classes.
     */
    public function store(StoreLearningItemRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $attachment = $request->file('attachment');

        $attributes = [
            'user_id' => $user->id,
            'folder_id' => $validated['folder_id'] ?? null,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'topic' => $validated['topic'] ?? null,
            'content' => $validated['content'] ?? null,
            'url' => $validated['type'] === 'link' ? $validated['url'] : null,
            'due_at' => $validated['due_at'] ?? null,
            'max_score' => $validated['max_score'] ?? 100,
            'is_graded' => $request->boolean(
                'is_graded',
                in_array($validated['type'], LearningItem::GRADED_BY_DEFAULT_TYPES, true),
            ),
        ];

        if ($attachment !== null) {
            $attributes['attachment_path'] = $attachment->store("library/{$user->id}", LearningItem::ATTACHMENT_DISK);
            $attributes['attachment_name'] = $attachment->getClientOriginalName();
            $attributes['attachment_mime'] = $attachment->getMimeType();
            $attributes['attachment_size'] = $attachment->getSize();
        }

        if ($validated['type'] === 'h5p') {
            try {
                $this->h5pPackages->extract(new LearningItem($attributes));
            } catch (ValidationException $exception) {
                Storage::disk(LearningItem::ATTACHMENT_DISK)->delete($attributes['attachment_path']);

                throw $exception;
            }
        }

        $item = DB::transaction(function () use ($attributes, $validated): LearningItem {
            $item = LearningItem::create($attributes);
            $item->classes()->sync($validated['class_ids'] ?? []);

            if ($item->type === 'poll') {
                $item->pollOptions()->createMany(
                    collect($validated['poll_options'])
                        ->values()
                        ->map(fn (string $label, int $position): array => ['label' => $label, 'position' => $position])
                        ->all(),
                );
            }

            return $item;
        });

        return back()->with('success', "{$item->typeLabel()} \"{$item->title}\" berhasil disimpan.");
    }

    /**
     * Update which classes and teachers a learning item is shared with.
     */
    public function share(ShareLearningItemRequest $request, LearningItem $learningItem): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->ensureManageable($learningItem, $user);

        $validated = $request->validated();
        $requestedClassIds = collect($validated['class_ids'])->map(fn (mixed $id): int => (int) $id);

        DB::transaction(function () use ($learningItem, $user, $requestedClassIds, $validated): void {
            $controllableClassIds = $learningItem->classes()
                ->when(! $user->isAdmin(), fn ($classes) => $classes->where('guru_id', $user->id))
                ->pluck('school_classes.id');

            $learningItem->classes()->detach($controllableClassIds->diff($requestedClassIds)->all());
            $learningItem->classes()->syncWithoutDetaching($requestedClassIds->all());
            $learningItem->sharedWith()->sync($validated['teacher_ids']);
        });

        return back()->with('success', "Pengaturan berbagi \"{$learningItem->title}\" berhasil diperbarui.");
    }

    /**
     * Toggle whether a learning item is included in the gradebook.
     */
    public function toggleGraded(Request $request, LearningItem $learningItem): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->ensureManageable($learningItem, $user);

        $learningItem->update(['is_graded' => ! $learningItem->is_graded]);

        return back()->with('success', $learningItem->is_graded
            ? "\"{$learningItem->title}\" sekarang masuk buku nilai."
            : "\"{$learningItem->title}\" dikeluarkan dari buku nilai. Nilai yang sudah ada tetap tersimpan.");
    }

    /**
     * Move a learning item into one of the owner's folders (or back to the library root).
     */
    public function move(MoveLearningItemRequest $request, LearningItem $learningItem): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($learningItem->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'Hanya pemilik yang dapat memindahkan konten ini.');
        }

        $learningItem->update(['folder_id' => $request->validated('folder_id')]);

        return back()->with('success', "\"{$learningItem->title}\" berhasil dipindahkan.");
    }

    /**
     * Copy a visible learning item (including its file) into the current user's own library.
     */
    public function copy(Request $request, LearningItem $learningItem): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $learningItem->isVisibleTo($user)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke konten ini.');
        }

        $copy = $learningItem->replicate(['folder_id', 'attachment_path']);
        $copy->user_id = $user->id;
        $copy->title = Str::limit("Salinan - {$learningItem->title}", 180, '');

        if ($learningItem->attachment_path !== null) {
            $disk = Storage::disk(LearningItem::ATTACHMENT_DISK);
            $extension = pathinfo($learningItem->attachment_path, PATHINFO_EXTENSION);
            $copyPath = "library/{$user->id}/".Str::random(40).($extension !== '' ? ".{$extension}" : '');

            if (! $disk->copy($learningItem->attachment_path, $copyPath)) {
                return back()->with('error', 'Berkas lampiran tidak dapat disalin. Hubungi pemilik konten.');
            }

            $copy->attachment_path = $copyPath;
        }

        DB::transaction(function () use ($copy, $learningItem): void {
            $copy->save();
            $copy->pollOptions()->createMany(
                $learningItem->pollOptions->map->only(['label', 'position'])->all(),
            );
        });

        if ($copy->type === 'h5p') {
            $this->h5pPackages->extract($copy);
        }

        return back()->with('success', "\"{$learningItem->title}\" disalin ke Pustaka Anda.");
    }

    /**
     * Download the attachment of a learning item the user is allowed to see.
     */
    public function download(Request $request, LearningItem $learningItem): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $learningItem->isVisibleTo($user)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke berkas ini.');
        }

        $disk = Storage::disk(LearningItem::ATTACHMENT_DISK);

        if ($learningItem->attachment_path === null || ! $disk->exists($learningItem->attachment_path)) {
            abort(Response::HTTP_NOT_FOUND, 'Berkas lampiran tidak ditemukan.');
        }

        return $disk->download($learningItem->attachment_path, $learningItem->attachment_name);
    }

    /**
     * Display an attachment inline; office documents are shown as a PDF rendition.
     */
    public function preview(Request $request, LearningItem $learningItem): BinaryFileResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $learningItem->isVisibleTo($user)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke berkas ini.');
        }

        if ($this->previews->kind($learningItem) === null) {
            abort(Response::HTTP_NOT_FOUND, 'Berkas ini tidak memiliki pratinjau.');
        }

        $preview = $this->previews->resolve($learningItem);

        if ($preview === null) {
            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'Pratinjau belum dapat dibuat. Silakan unduh berkasnya.');
        }

        return response()->file($preview['path'], [
            'Content-Type' => $preview['content_type'],
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /**
     * Soft-delete a learning item (move to trash).
     */
    public function destroy(Request $request, LearningItem $learningItem): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->ensureManageable($learningItem, $user);

        $learningItem->delete();

        return back()->with('success', "Item \"{$learningItem->title}\" dipindahkan ke Tempat Sampah.");
    }

    /**
     * Abort unless the user owns the item or is an admin.
     */
    private function ensureManageable(LearningItem $learningItem, User $user): void
    {
        if (! $learningItem->isManageableBy($user)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak diizinkan mengubah konten ini.');
        }
    }

    /**
     * Pick the library view the user is allowed to open.
     */
    private function resolveView(User $user, string $requested): string
    {
        $allowed = match (true) {
            $user->isAdmin() => ['mine', 'all'],
            $user->isGuru() => ['mine', 'shared'],
            default => ['class'],
        };

        return in_array($requested, $allowed, true) ? $requested : $allowed[0];
    }

    /**
     * Shape a learning item for the library page.
     *
     * @return array<string, mixed>
     */
    private function presentItem(LearningItem $item, User $user): array
    {
        $canManage = $item->isManageableBy($user);

        return [
            'id' => $item->id,
            'title' => $item->title,
            'type' => $item->type,
            'type_label' => $item->typeLabel(),
            'topic' => $item->topic,
            'content' => $item->content,
            'url' => $item->url,
            'h5p_player_url' => $item->h5pPlayerUrl(),
            'poll' => $item->type === 'poll' ? $this->presentPoll($item, $user) : null,
            'folder_id' => $item->folder_id,
            'attachment_name' => $item->attachment_path !== null ? $item->attachment_name : null,
            'preview_kind' => $this->previews->kind($item),
            'attachment_size' => $item->attachment_size !== null ? Number::fileSize($item->attachment_size) : null,
            'class_name' => $item->classLabel(),
            'class_ids' => $item->classes->modelKeys(),
            'teacher_ids' => $canManage ? $item->sharedWith->modelKeys() : [],
            'shared_teacher_names' => $canManage ? $item->sharedWith->pluck('name')->all() : [],
            'author_name' => $item->author->name ?? '-',
            'due_at' => $item->due_at?->format('d/m/Y H:i'),
            'max_score' => $item->max_score,
            'is_graded' => $item->is_graded,
            'is_owner' => $item->user_id === $user->id,
            'can_manage' => $canManage,
            'can_copy' => ! $user->isSiswa(),
            'created_at' => $item->created_at?->format('d/m/Y H:i'),
        ];
    }

    /**
     * Shape a poll for the library page. Students only see the tally after they have voted.
     *
     * @return array{options: array<int, array{id: int, label: string, votes: int|null}>, total_votes: int|null, my_option_id: int|null, can_vote: bool}
     */
    private function presentPoll(LearningItem $item, User $user): array
    {
        $myOptionId = $item->pollVotes->first()?->poll_option_id;
        $showResults = ! $user->isSiswa() || $myOptionId !== null;

        return [
            'options' => $item->pollOptions->map(fn (PollOption $option): array => [
                'id' => $option->id,
                'label' => $option->label,
                'votes' => $showResults ? (int) $option->votes_count : null,
            ])->all(),
            'total_votes' => $showResults ? (int) $item->pollOptions->sum('votes_count') : null,
            'my_option_id' => $myOptionId,
            'can_vote' => $user->isSiswa(),
        ];
    }

    /**
     * Build a flat list of the user's folders labelled with their full path.
     *
     * @param  Collection<int, Folder>  $folders
     * @return array<int, array{id: int, label: string}>
     */
    private function folderOptions(Collection $folders): array
    {
        $byId = $folders->keyBy('id');

        return $folders
            ->map(function (Folder $folder) use ($byId): array {
                $names = [];
                $cursor = $folder;

                while ($cursor !== null) {
                    array_unshift($names, $cursor->name);
                    $cursor = $cursor->parent_id !== null ? $byId->get($cursor->parent_id) : null;
                }

                return ['id' => $folder->id, 'label' => implode(' / ', $names)];
            })
            ->sortBy('label')
            ->values()
            ->all();
    }

    /**
     * Get classes relevant to the user for dropdown filters/forms.
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function getAvailableClasses(User $user): array
    {
        $query = SchoolClass::query()->orderBy('name');

        if ($user->isGuru()) {
            $query->where('guru_id', $user->id);
        } elseif ($user->isSiswa()) {
            $query->whereHas('students', fn (Builder $q) => $q->where('users.id', $user->id));
        }

        return $query->get(['id', 'name', 'subject'])
            ->map(fn (SchoolClass $c): array => [
                'id' => $c->id,
                'label' => "{$c->name} - {$c->subject}",
            ])
            ->all();
    }

    /**
     * Get the other teachers a guru or admin can share content with.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function getShareableTeachers(User $user): array
    {
        if ($user->isSiswa()) {
            return [];
        }

        return User::query()
            ->where('role', UserRole::Guru)
            ->whereKeyNot($user->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $teacher): array => ['id' => $teacher->id, 'name' => $teacher->name])
            ->all();
    }
}
