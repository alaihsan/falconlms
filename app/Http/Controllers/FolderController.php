<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FolderController extends Controller
{
    /**
     * Create a folder in the current user's library.
     */
    public function store(StoreFolderRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $folder = $user->folders()->create([
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return back()->with('success', "Folder \"{$folder->name}\" berhasil dibuat.");
    }

    /**
     * Rename a folder owned by the current user.
     */
    public function update(StoreFolderRequest $request, Folder $folder): RedirectResponse
    {
        $this->ensureOwner($request, $folder);

        $folder->update(['name' => $request->validated('name')]);

        return back()->with('success', "Folder diubah namanya menjadi \"{$folder->name}\".");
    }

    /**
     * Delete an empty folder owned by the current user.
     */
    public function destroy(Request $request, Folder $folder): RedirectResponse
    {
        $this->ensureOwner($request, $folder);

        if ($folder->children()->exists() || $folder->learningItems()->exists()) {
            return back()->with('error', "Folder \"{$folder->name}\" masih berisi konten. Pindahkan atau hapus isinya terlebih dahulu.");
        }

        $folder->delete();

        return back()->with('success', "Folder \"{$folder->name}\" berhasil dihapus.");
    }

    /**
     * Abort unless the current user owns the folder.
     */
    private function ensureOwner(Request $request, Folder $folder): void
    {
        if ($folder->user_id !== $request->user()?->id) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak diizinkan mengubah folder ini.');
        }
    }
}
