<?php

namespace App\Http\Controllers\Content;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Folder;
use App\Models\Item;
use App\Models\Space;
use App\Models\User;
use App\Support\CreationContext;
use App\Support\Payload;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    /**
     * Show the teacher's own library (Pustaka Saya), like a Drive folder view.
     */
    public function show(Request $request, ?Folder $folder = null): Response
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasRole(Role::Teacher), 403);

        $library = Space::personalLibraryFor($user);

        // Someone else's folder is reported as missing, not forbidden.
        abort_if($folder && $folder->space_id !== $library->id, 404);

        $breadcrumbs = [['title' => $library->displayName(), 'href' => route('library.show')]];

        if ($folder) {
            foreach ([...$folder->ancestors(), $folder] as $crumb) {
                $breadcrumbs[] = ['title' => $crumb->name, 'href' => route('library.show', $crumb)];
            }
        }

        return Inertia::render('library/Show', [
            'context' => CreationContext::forLibrary($library, $folder, $user)->toArray(),
            'breadcrumbs' => $breadcrumbs,
            'folder' => $folder ? Payload::folder($folder) + ['parentId' => $folder->parent_id] : null,
            'folders' => Folder::query()
                ->where('space_id', $library->id)
                ->where('parent_id', $folder?->id)
                ->orderBy('name')
                ->get()
                ->map(fn (Folder $child) => Payload::folder($child)),
            'items' => Item::query()
                ->with('attachments')
                ->where('space_id', $library->id)
                ->where('folder_id', $folder?->id)
                ->orderBy('title')
                ->get()
                ->map(fn (Item $item) => Payload::item($item)),
        ]);
    }
}
