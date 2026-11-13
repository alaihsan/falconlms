<?php

namespace App\Http\Controllers;

use App\Models\LearningItem;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class TrashController extends Controller
{
    /**
     * Display soft-deleted classes and learning items.
     */
    public function index(Request $request): InertiaResponse
    {
        /** @var User $user */
        $user = $request->user();

        $trashedClasses = collect();
        $trashedItems = collect();

        if (! $user->isSiswa()) {
            $classQuery = SchoolClass::onlyTrashed()->with('guru:id,name');
            $itemQuery = LearningItem::onlyTrashed()->with(['classes:id,name,subject', 'author:id,name']);

            if ($user->isGuru()) {
                $classQuery->where('guru_id', $user->id);
                $itemQuery->where('user_id', $user->id);
            }

            $trashedClasses = $classQuery->orderByDesc('deleted_at')->get()->map(fn (SchoolClass $c): array => [
                'id' => $c->id,
                'kind' => 'class',
                'title' => "{$c->name} ({$c->subject})",
                'subtitle' => "Kode: {$c->code} • Guru: ".($c->guru->name ?? '-'),
                'deleted_at' => $c->deleted_at?->format('d/m/Y H:i'),
            ]);

            $trashedItems = $itemQuery->orderByDesc('deleted_at')->get()->map(fn (LearningItem $item): array => [
                'id' => $item->id,
                'kind' => 'item',
                'title' => $item->title,
                'subtitle' => $item->typeLabel().' • '.$item->classLabel(),
                'deleted_at' => $item->deleted_at?->format('d/m/Y H:i'),
            ]);
        }

        return Inertia::render('Trash/Index', [
            'items' => $trashedClasses->concat($trashedItems)->values()->all(),
        ]);
    }

    /**
     * Restore a soft-deleted class or learning item.
     */
    public function restore(Request $request, string $kind, int $id): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($kind === 'class') {
            $model = SchoolClass::onlyTrashed()->findOrFail($id);
            if (! $user->isAdmin() && $model->guru_id !== $user->id) {
                abort(Response::HTTP_FORBIDDEN);
            }
            $model->restore();

            return back()->with('success', "Kelas \"{$model->name}\" berhasil dipulihkan.");
        }

        $item = LearningItem::onlyTrashed()->findOrFail($id);
        if (! $user->isAdmin() && $item->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }
        $item->restore();

        return back()->with('success', "Item \"{$item->title}\" berhasil dipulihkan.");
    }

    /**
     * Permanently delete a soft-deleted class or learning item.
     */
    public function forceDelete(Request $request, string $kind, int $id): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($kind === 'class') {
            $model = SchoolClass::onlyTrashed()->findOrFail($id);
            if (! $user->isAdmin() && $model->guru_id !== $user->id) {
                abort(Response::HTTP_FORBIDDEN);
            }
            $name = $model->name;
            $model->forceDelete();

            return back()->with('success', "Kelas \"{$name}\" dihapus permanen.");
        }

        $item = LearningItem::onlyTrashed()->findOrFail($id);
        if (! $user->isAdmin() && $item->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN);
        }
        $title = $item->title;
        $item->forceDelete();

        return back()->with('success', "Item \"{$title}\" dihapus permanen.");
    }
}
