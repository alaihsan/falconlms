<?php

namespace App\Http\Controllers\Courses;

use App\Actions\Courses\ImportFromLibrary;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LibraryImportController extends Controller
{
    /**
     * Copy folders and items from the teacher's library into the class as drafts.
     */
    public function store(Request $request, Space $course, ImportFromLibrary $import): RedirectResponse
    {
        Gate::authorize('manage', $course);
        abort_unless($request->user()->hasRole(Role::Teacher), 403);

        $library = Space::personalLibraryFor($request->user());

        $data = $request->validate([
            'folders' => ['array', 'max:50'],
            'folders.*' => ['integer', Rule::exists('folders', 'id')->where('space_id', $library->id)->whereNull('deleted_at')],
            'items' => ['array', 'max:200'],
            'items.*' => ['integer', Rule::exists('items', 'id')->where('space_id', $library->id)->whereNull('deleted_at')],
            'topic_id' => ['nullable', 'integer', Rule::exists('topics', 'id')->where('space_id', $course->id)],
        ]);

        $count = $import->handle(
            $library,
            $course,
            $request->user(),
            $data['folders'] ?? [],
            $data['items'] ?? [],
            isset($data['topic_id']) ? Topic::query()->findOrFail((int) $data['topic_id']) : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count item copied as a draft to :place.|:count items copied as drafts to :place.', $count, ['place' => $course->name]),
        ]);

        return redirect()->route('courses.classwork', $course);
    }
}
