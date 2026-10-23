<?php

namespace App\Http\Controllers;

use App\Enums\MemberRole;
use App\Enums\SubmissionStatus;
use App\Models\Item;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TodoController extends Controller
{
    /**
     * Students see their assignments by state; teachers see work waiting to be graded.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $studentCourses = $user->courses()->wherePivot('role', MemberRole::Student->value)->pluck('spaces.id');
        $teacherCourses = $user->courses()->wherePivot('role', MemberRole::Teacher->value)->pluck('spaces.id');

        $assignments = Item::query()
            ->with('space')
            ->whereIn('space_id', $studentCourses)
            ->where('type', 'assignment')
            ->published()
            ->orderByRaw('due_at is null, due_at')
            ->get();

        $submissions = Submission::query()
            ->where('student_id', $user->id)
            ->whereIn('item_id', $assignments->pluck('id'))
            ->get()
            ->keyBy('item_id');

        $groups = ['assigned' => [], 'missing' => [], 'done' => []];

        foreach ($assignments as $assignment) {
            $submission = $submissions->get($assignment->id);
            $group = match (true) {
                $submission?->isTurnedIn() ?? false => 'done',
                $assignment->due_at !== null && $assignment->due_at->isPast() => 'missing',
                default => 'assigned',
            };

            $groups[$group][] = [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'course' => $assignment->space->name,
                'dueAt' => $assignment->due_at?->toIso8601String(),
                'status' => $submission?->status->value ?? 'assigned',
                'score' => $submission?->status === SubmissionStatus::Returned && $submission->score !== null ? (float) $submission->score : null,
            ];
        }

        $toGrade = Item::query()
            ->with('space')
            ->whereIn('space_id', $teacherCourses)
            ->where('type', 'assignment')
            ->withCount(['submissions as waiting_count' => fn ($query) => $query->where('status', SubmissionStatus::TurnedIn)])
            ->get()
            ->filter(fn (Item $item) => $item->getAttribute('waiting_count') > 0)
            ->map(fn (Item $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'course' => $item->space->name,
                'waiting' => (int) $item->getAttribute('waiting_count'),
            ])
            ->values();

        return Inertia::render('Todo', [
            'breadcrumbs' => [['title' => __('To-do'), 'href' => route('todo')]],
            'groups' => $groups,
            'toGrade' => $toGrade,
        ]);
    }
}
