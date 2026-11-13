<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AssignmentSubmission;
use App\Models\LearningItem;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard / home screen.
     */
    public function index(Request $request): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        $stats = match ($user?->role) {
            UserRole::Admin => [
                'total_users' => User::count(),
                'total_guru' => User::where('role', UserRole::Guru)->count(),
                'total_siswa' => User::where('role', UserRole::Siswa)->count(),
                'total_classes' => SchoolClass::count(),
            ],
            UserRole::Guru => [
                'total_classes' => $user->teachingClasses()->count(),
                'total_items' => LearningItem::where('user_id', $user->id)->count(),
                'pending_grading' => AssignmentSubmission::whereNull('score')
                    ->whereHas('learningItem', fn (Builder $q) => $q->where('user_id', $user->id))
                    ->count(),
            ],
            UserRole::Siswa => [
                'enrolled_classes' => $user->enrolledClasses()->count(),
                'pending_tasks' => LearningItem::whereIn('type', ['assignment', 'quiz'])
                    ->visibleTo($user)
                    ->whereDoesntHave('submissions', fn (Builder $q) => $q->where('siswa_id', $user->id))
                    ->count(),
                'completed_tasks' => AssignmentSubmission::where('siswa_id', $user->id)->count(),
            ],
            default => [],
        };

        $recentClasses = [];
        $recentItems = [];

        if ($user) {
            $classQuery = SchoolClass::query()->with('guru:id,name')->withCount(['students', 'learningItems']);
            if ($user->isGuru()) {
                $classQuery->where('guru_id', $user->id);
            } elseif ($user->isSiswa()) {
                $classQuery->whereHas('students', fn (Builder $q) => $q->where('users.id', $user->id));
            }

            $recentClasses = $classQuery->orderByDesc('created_at')->limit(4)->get()->map(fn (SchoolClass $c): array => [
                'id' => $c->id,
                'name' => $c->name,
                'subject' => $c->subject,
                'code' => $c->code,
                'guru_name' => $c->guru->name ?? '-',
                'students_count' => $c->students_count,
                'items_count' => $c->learning_items_count,
            ])->all();

            $itemQuery = LearningItem::query()->with(['classes:id,name,subject', 'author:id,name']);
            if ($user->isGuru()) {
                $itemQuery->where('user_id', $user->id);
            } elseif ($user->isSiswa()) {
                $itemQuery->visibleTo($user);
            }

            $recentItems = $itemQuery->orderByDesc('created_at')->limit(5)->get()->map(fn (LearningItem $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'type' => $item->type,
                'type_label' => $item->typeLabel(),
                'class_name' => $item->classLabel(),
                'author_name' => $item->author->name ?? '-',
                'due_at' => $item->due_at?->format('d/m/Y H:i'),
                'created_at' => $item->created_at?->format('d/m/Y H:i'),
            ])->all();
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentClasses' => $recentClasses,
            'recentItems' => $recentItems,
        ]);
    }
}
