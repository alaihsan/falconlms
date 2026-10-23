<?php

namespace App\Http\Controllers\Submissions;

use App\Actions\Submissions\SubmissionWorkflow;
use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Item;
use App\Models\Submission;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * The student's side of an assignment: attach work, turn it in, unsubmit.
 */
class SubmissionController extends Controller
{
    public function __construct(private SubmissionWorkflow $workflow) {}

    public function addFiles(Request $request, Item $item): RedirectResponse
    {
        Gate::authorize('submit', $item);

        $data = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => Uploads::rules(),
        ]);

        $submission = $this->workflow->forStudent($item, $request->user());
        $this->workflow->ensureEditable($submission);

        foreach ($data['files'] as $file) {
            Uploads::attach($submission, $file, $request->user());
        }

        return back();
    }

    public function removeFile(Request $request, Attachment $attachment): RedirectResponse
    {
        $submission = $attachment->attachable;

        abort_unless($submission instanceof Submission && $submission->student_id === $request->user()->id, 403);
        $this->workflow->ensureEditable($submission);

        $attachment->delete();

        return back();
    }

    public function turnIn(Request $request, Item $item): RedirectResponse
    {
        Gate::authorize('submit', $item);

        $data = $request->validate(['body' => ['nullable', 'string', 'max:10000']]);
        $this->workflow->turnIn($item, $request->user(), $data['body'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Turned in.')]);

        return back();
    }

    public function unsubmit(Request $request, Item $item): RedirectResponse
    {
        Gate::authorize('submit', $item);

        $this->workflow->unsubmit($item, $request->user());

        return back();
    }
}
