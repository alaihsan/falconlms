<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportGradebookRequest;
use App\Http\Requests\UpdateGradebookRequest;
use App\Models\AssignmentSubmission;
use App\Models\LearningItem;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\GradebookService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradeController extends Controller
{
    public function __construct(private GradebookService $gradebook) {}

    /**
     * Display the class gradebook grid for Guru/Admin, or the personal grade history for Siswa.
     */
    public function index(Request $request): InertiaResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $user->isSiswa()
            ? $this->studentHistory($user)
            : $this->classGradebook($request, $user);
    }

    /**
     * Save a batch of edited gradebook cells for a class.
     */
    public function update(UpdateGradebookRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->ensureTeaches($request, $schoolClass);

        /** @var array<int, array{learning_item_id: int, siswa_id: int, score: int|null}> $entries */
        $entries = collect($request->validated('scores'))
            ->map(fn (array $entry): array => [
                'learning_item_id' => (int) $entry['learning_item_id'],
                'siswa_id' => (int) $entry['siswa_id'],
                'score' => $entry['score'] === null ? null : (int) $entry['score'],
            ])
            ->all();

        $changed = $this->gradebook->saveScores($schoolClass, $entries);

        return back()->with('success', "{$changed} nilai berhasil disimpan.");
    }

    /**
     * Download the class gradebook as a CSV file.
     */
    public function export(Request $request, SchoolClass $schoolClass): StreamedResponse
    {
        $this->ensureTeaches($request, $schoolClass);

        return $this->gradebook->export($schoolClass);
    }

    /**
     * Import scores for a class from a CSV file in the exported layout.
     */
    public function import(ImportGradebookRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        $this->ensureTeaches($request, $schoolClass);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $result = $this->gradebook->import($schoolClass, $file);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Render the editable gradebook grid of the selected class.
     */
    private function classGradebook(Request $request, User $user): InertiaResponse
    {
        $classes = SchoolClass::query()
            ->when($user->isGuru(), fn (Builder $q) => $q->where('guru_id', $user->id))
            ->orderBy('name')
            ->orderBy('subject')
            ->get(['id', 'name', 'subject']);

        $selected = $classes->firstWhere('id', $request->integer('class_id')) ?? $classes->first();

        $students = $selected ? $this->gradebook->students($selected) : new Collection;
        $items = $selected ? $this->gradebook->gradedItems($selected) : new Collection;

        return Inertia::render('Grades/Book', [
            'classes' => $classes->map(fn (SchoolClass $class): array => [
                'id' => $class->id,
                'label' => "{$class->name} - {$class->subject}",
            ])->all(),
            'selectedClassId' => $selected?->id,
            'students' => $students->map(fn (User $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'identifier' => $student->username ?? $student->email,
            ])->all(),
            'items' => $items->map(fn (LearningItem $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'type_label' => $item->typeLabel(),
                'max_score' => $item->max_score,
            ])->all(),
            'scores' => (object) $this->gradebook->scores($students, $items),
        ]);
    }

    /**
     * Abort unless the user teaches the class or is an admin.
     */
    private function ensureTeaches(Request $request, SchoolClass $schoolClass): void
    {
        $user = $request->user();

        if ($user === null || (! $user->isAdmin() && $schoolClass->guru_id !== $user->id)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak mengajar di kelas ini.');
        }
    }

    /**
     * Render the grade history of a student.
     */
    private function studentHistory(User $user): InertiaResponse
    {
        $query = AssignmentSubmission::query()
            ->with(['learningItem.classes:id,name,subject', 'siswa:id,name,username,email']);

        $query->where('siswa_id', $user->id)->whereHas('learningItem');

        $submissions = $query
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        $gradedScores = $submissions->whereNotNull('score')->pluck('score');
        $averageScore = $gradedScores->isNotEmpty()
            ? round((float) $gradedScores->avg(), 1)
            : null;

        $grades = $submissions->map(fn (AssignmentSubmission $sub): array => [
            'id' => $sub->id,
            'item_title' => $sub->learningItem->title ?? '-',
            'item_type' => $sub->learningItem?->typeLabel() ?? 'Tugas',
            'class_name' => $sub->learningItem?->classLabel() ?? '-',
            'siswa_name' => $sub->siswa->name ?? '-',
            'siswa_identifier' => $sub->siswa->username ?? $sub->siswa->email ?? '-',
            'score' => $sub->score,
            'max_score' => $sub->learningItem->max_score ?? 100,
            'feedback' => $sub->feedback,
            'submitted_at' => $sub->submitted_at?->format('d/m/Y H:i'),
            'graded_at' => $sub->graded_at?->format('d/m/Y H:i'),
        ]);

        return Inertia::render('Grades/Index', [
            'grades' => $grades,
            'summary' => [
                'total_submissions' => $submissions->count(),
                'graded_count' => $gradedScores->count(),
                'pending_count' => $submissions->whereNull('score')->count(),
                'average_score' => $averageScore,
            ],
        ]);
    }
}
