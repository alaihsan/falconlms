<?php

namespace App\Http\Controllers\Content;

use App\Actions\Content\StoreUploads;
use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\Space;
use App\Models\Topic;
use App\Support\Uploads;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UploadController extends Controller
{
    /**
     * Save files dropped or picked with "+ Baru" into the current folder or topic.
     */
    public function store(Request $request, Space $space, StoreUploads $storeUploads): RedirectResponse
    {
        Gate::authorize('manage', $space);

        $data = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:'.config('falcon.uploads.max_files')],
            'files.*' => Uploads::rules(),
            'paths' => ['array'],
            'paths.*' => ['nullable', 'string', 'max:1000'],
            'folder_id' => $space->isCourse()
                ? ['prohibited']
                : ['nullable', 'integer', Rule::exists('folders', 'id')->where('space_id', $space->id)->whereNull('deleted_at')],
            'topic_id' => $space->isCourse()
                ? ['nullable', 'integer', Rule::exists('topics', 'id')->where('space_id', $space->id)]
                : ['prohibited'],
        ]);

        $count = $storeUploads->handle(
            $space,
            isset($data['folder_id']) ? Folder::query()->findOrFail((int) $data['folder_id']) : null,
            isset($data['topic_id']) ? Topic::query()->findOrFail((int) $data['topic_id']) : null,
            array_values($data['files']),
            array_values($data['paths'] ?? []),
            $request->user(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $space->isCourse()
                ? trans_choice(':count file saved as a draft material.|:count files saved as draft materials.', $count)
                : trans_choice(':count file uploaded.|:count files uploaded.', $count),
        ]);

        return back();
    }
}
