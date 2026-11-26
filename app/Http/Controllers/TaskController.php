<?php

namespace App\Http\Controllers;

use App\Http\Requests\GradeSubmissionRequest;
use App\Http\Requests\SubmitAssignmentRequest;
use App\Models\AssignmentSubmission;
use App\Models\LearningItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    /**
     * Display assignments and quizzes for Guru, Siswa, or Admin.
     */
    public function index(Request $request): InertiaResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = LearningItem::query()
            ->whereIn('type', ['assignment', 'quiz'])
            ->with(['classes:id,name,subject,guru_id', 'author:id,name']);

        if ($user->isGuru()) {
            $query->where(function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhereHas('classes', fn (Builder $sub) => $sub->where('guru_id', $user->id));
            })->with(['submissions.siswa:id,name,email,username']);
        } elseif ($user->isSiswa()) {
            $query->visibleTo($user)
                ->with(['submissions' => fn ($q) => $q->where('siswa_id', $user->id)]);
        } else {
            $query->with(['submissions.siswa:id,name,email,username']);
        }

        $tasks = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (LearningItem $item) use ($user): array {
                $mySubmission = $user->isSiswa() ? $item->submissions->first() : null;

                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'type' => $item->type,
                    'type_label' => $item->typeLabel(),
                    'topic' => $item->topic,
                    'content' => $item->content,
                    'class_id' => $item->classes->first()?->id,
                    'class_name' => $item->classLabel(),
                    'author_name' => $item->author->name ?? '-',
                    'due_at' => $item->due_at?->format('d/m/Y H:i'),
                    'max_score' => $item->max_score,
                    'my_submission' => $mySubmission ? [
                        'id' => $mySubmission->id,
                        'answer_text' => $mySubmission->answer_text,
                        'score' => $mySubmission->score,
                        'feedback' => $mySubmission->feedback,
                        'submitted_at' => $mySubmission->submitted_at?->format('d/m/Y H:i'),
                        'graded_at' => $mySubmission->graded_at?->format('d/m/Y H:i'),
                    ] : null,
                    'submissions' => (! $user->isSiswa()) ? $item->submissions->map(fn (AssignmentSubmission $sub): array => [
                        'id' => $sub->id,
                        'siswa_name' => $sub->siswa->name ?? '-',
                        'siswa_username' => $sub->siswa->username ?? $sub->siswa->email ?? '-',
                        'answer_text' => $sub->answer_text,
                        'score' => $sub->score,
                        'feedback' => $sub->feedback,
                        'submitted_at' => $sub->submitted_at?->format('d/m/Y H:i'),
                        'graded_at' => $sub->graded_at?->format('d/m/Y H:i'),
                    ])->values()->all() : [],
                ];
            });

        $classes = $user->isGuru()
            ? $user->teachingClasses()->orderBy('name')->get(['id', 'name', 'subject'])->map(fn ($c) => [
                'id' => $c->id,
                'label' => "{$c->name} - {$c->subject}",
            ])->all()
            : [];

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'classes' => $classes,
        ]);
    }

    /**
     * Submit or update an assignment answer as a Siswa.
     */
    public function submit(SubmitAssignmentRequest $request, LearningItem $learningItem): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! in_array($learningItem->type, ['assignment', 'quiz'], true)) {
            abort(Response::HTTP_BAD_REQUEST, 'Konten ini bukan tugas atau kuis.');
        }

        if (! $learningItem->isVisibleTo($user)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda belum terdaftar di kelas untuk tugas ini.');
        }

        $isAlreadyGraded = AssignmentSubmission::query()
            ->where('learning_item_id', $learningItem->id)
            ->where('siswa_id', $user->id)
            ->whereNotNull('score')
            ->exists();

        if ($isAlreadyGraded) {
            return back()->with('error', "Jawaban untuk \"{$learningItem->title}\" sudah dinilai sehingga tidak dapat diubah lagi.");
        }

        AssignmentSubmission::updateOrCreate(
            [
                'learning_item_id' => $learningItem->id,
                'siswa_id' => $user->id,
            ],
            [
                'answer_text' => $request->string('answer_text')->trim()->value(),
                'submitted_at' => now(),
            ],
        );

        return back()->with('success', "Jawaban untuk \"{$learningItem->title}\" berhasil dikumpulkan.");
    }

    /**
     * Grade a student's submission as a Guru or Admin.
     */
    public function grade(GradeSubmissionRequest $request, AssignmentSubmission $submission): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $learningItem = $submission->learningItem;

        if ($learningItem === null || ! $learningItem->isGradableBy($user)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak berwenang menilai tugas ini.');
        }

        $validated = $request->validated();

        $submission->update([
            'score' => $validated['score'],
            'feedback' => $validated['feedback'] ?? null,
            'graded_at' => now(),
        ]);

        return back()->with('success', 'Nilai dan umpan balik berhasil disimpan.');
    }
}
