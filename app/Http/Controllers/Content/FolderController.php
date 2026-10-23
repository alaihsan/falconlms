<?php

namespace App\Http\Controllers\Content;

use App\Actions\Content\CreateFolder;
use App\Actions\Content\DeleteFolder;
use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\Space;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FolderController extends Controller
{
    public function store(Request $request, Space $space, CreateFolder $createFolder): RedirectResponse
    {
        Gate::authorize('manage', $space);
        abort_unless($space->isLibrary(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', Rule::exists('folders', 'id')->where('space_id', $space->id)->whereNull('deleted_at')],
        ]);

        $parent = isset($data['parent_id']) ? Folder::query()->findOrFail((int) $data['parent_id']) : null;
        $createFolder->handle($space, $parent, $data['name'], $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Folder created.')]);

        return back();
    }

    public function update(Request $request, Folder $folder): RedirectResponse
    {
        Gate::authorize('manage', $folder->space);

        $folder->update($request->validate(['name' => ['required', 'string', 'max:100']]));

        return back();
    }

    public function destroy(Folder $folder, DeleteFolder $deleteFolder): RedirectResponse
    {
        Gate::authorize('manage', $folder->space);

        $deleteFolder->handle($folder);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Folder deleted.')]);

        return redirect()->route('library.show', $folder->parent_id);
    }
}
