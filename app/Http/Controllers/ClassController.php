<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinClassRequest;
use App\Http\Requests\StoreClassRequest;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class ClassController extends Controller
{
    /**
     * Display the list of classes for the authenticated user.
     */
    public function index(Request $request): InertiaResponse
    {
        /** @var User $user */
        $user = $request->user();
        $search = $request->string('search')->trim()->value();

        $query = SchoolClass::query()
            ->with('guru:id,name')
            ->withCount(['students', 'learningItems'])
            ->when($search !== '', function (Builder $q) use ($search): void {
                $q->where(function (Builder $sub) use ($search): void {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            });

        if ($user->isGuru()) {
            $query->where('guru_id', $user->id);
        } elseif ($user->isSiswa()) {
            $query->whereHas('students', fn (Builder $q) => $q->where('users.id', $user->id));
        }

        $classes = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (SchoolClass $schoolClass): array => [
                'id' => $schoolClass->id,
                'name' => $schoolClass->name,
                'subject' => $schoolClass->subject,
                'room' => $schoolClass->room,
                'code' => $schoolClass->code,
                'description' => $schoolClass->description,
                'guru_name' => $schoolClass->guru->name ?? '-',
                'students_count' => $schoolClass->students_count,
                'items_count' => $schoolClass->learning_items_count,
                'created_at' => $schoolClass->created_at?->format('d/m/Y'),
            ]);

        return Inertia::render('Classes/Index', [
            'classes' => $classes,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a new class created by Guru or Admin.
     */
    public function store(StoreClassRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        do {
            $code = strtoupper(Str::random(6));
        } while (SchoolClass::withTrashed()->where('code', $code)->exists());

        SchoolClass::create([
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'room' => $validated['room'] ?? null,
            'description' => $validated['description'] ?? null,
            'code' => $code,
            'guru_id' => $user->id,
        ]);

        return redirect()->route('classes.index')
            ->with('success', "Kelas \"{$validated['name']}\" berhasil dibuat dengan kode gabung {$code}.");
    }

    /**
     * Allow a student (siswa) to join a class by code.
     */
    public function join(JoinClassRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $code = strtoupper(trim($request->string('code')->value()));

        $schoolClass = SchoolClass::where('code', $code)->first();

        if (! $schoolClass) {
            return back()->with('error', "Kode kelas \"{$code}\" tidak ditemukan.");
        }

        if ($schoolClass->students()->where('users.id', $user->id)->exists()) {
            return back()->with('error', "Anda sudah bergabung di kelas \"{$schoolClass->name}\".");
        }

        $schoolClass->students()->attach($user->id);

        return redirect()->route('classes.index')
            ->with('success', "Berhasil bergabung ke kelas \"{$schoolClass->name}\".");
    }

    /**
     * Soft-delete a class (move to trash).
     */
    public function destroy(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->isAdmin() && $schoolClass->guru_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses untuk menghapus kelas ini.');
        }

        $schoolClass->delete();

        return redirect()->route('classes.index')
            ->with('success', "Kelas \"{$schoolClass->name}\" dipindahkan ke Tempat Sampah.");
    }
}
