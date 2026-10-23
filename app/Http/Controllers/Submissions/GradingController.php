<?php

namespace App\Http\Controllers\Submissions;

use App\Actions\Submissions\SubmissionWorkflow;
use App\Enums\MemberRole;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Submission;
use App\Models\User;
use App\Support\CreationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The teacher's side of an assignment: see every student's work, score it and return it.
 */
class GradingController extends Controller
{
    public function index(Request $request, Item $item): Response
    {
        Gate::authorize('update', $item);
        $course = $item->space;
        abort_unless($item->isAssignment() && $course->isCourse(), 404);

        $submissions = Submission::query()->with('attachments')->where('item_id', $item->id)->get()->keyBy('student_id');
        $students = $course->users()->wherePivot('role', MemberRole::Student->value)->orderBy('name')->get(['users.id', 'users.name', 'users.username']);

        return Inertia::render('items/Submissions', [
            'item' => ['id' => $item->id, 'title' => $item->title, 'maxPoints' => $item->max_points, 'dueAt' => $item->due_at?->toIso8601String()],
            'context' => CreationContext::forItem($item, $request->user())->toArray(),
            'breadcrumbs' => [
                ['title' => $course->name, 'href' => route('courses.show', $course)],
                ['title' => $item->title, 'href' => route('items.show', $item)],
                ['title' => __('Student work'), 'href' => route('submissions.index', $item)],
            ],
            'students' => $students->map(function (User $student) use ($submissions, $item) {
                /** @var Submission|null $submission */
                $submission = $submissions->get($student->id);

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'username' => $student->username,
                    'status' => $submission?->status->value ?? 'assigned',
                    'turnedInAt' => $submission?->turned_in_at?->toIso8601String(),
                    'isLate' => $submission?->isLate($item) ?? false,
                    'body' => $submission?->body,
                    'score' => $submission?->score !== null ? (float) $submission->score : null,
                    'feedback' => $submission?->feedback,
                    'attachments' => $submission ? $submission->attachments->map->toPayload()->values() : [],
                ];
            })->values(),
        ]);
    }

    public function grade(Request $request, Item $item, User $student, SubmissionWorkflow $workflow): RedirectResponse
    {
        Gate::authorize('update', $item);
        abort_unless($item->isAssignment() && $item->space->roleOf($student) === MemberRole::Student, 404);

        $data = $request->validate([
            'score' => ['nullable', 'numeric', 'min:0', 'max:'.($item->max_points ?? 1000)],
            'feedback' => ['nullable', 'string', 'max:5000'],
            'return' => ['boolean'],
        ]);

        $workflow->grade(
            $item,
            $student,
            isset($data['score']) ? (float) $data['score'] : null,
            $data['feedback'] ?? null,
            (bool) ($data['return'] ?? false),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => ($data['return'] ?? false) ? __('Returned to :name.', ['name' => $student->name]) : __('Saved.')]);

        return back();
    }
}
