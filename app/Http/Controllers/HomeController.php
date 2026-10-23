<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Item;
use App\Models\Space;
use App\Models\SpaceMember;
use App\Models\User;
use App\Support\CreationContext;
use App\Support\Payload;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Show the home page (Beranda).
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $roles = SpaceMember::query()->where('user_id', $user->id)->pluck('role', 'space_id');

        $props = [
            'context' => CreationContext::forHome($user)->toArray(),
            'courses' => $user->courses()->orderBy('name')->get()
                ->map(fn (Space $course) => Payload::course($course) + ['role' => $roles->get($course->id)?->value])
                ->values(),
        ];

        if ($user->hasRole(Role::Teacher)) {
            $props['recent'] = Item::query()
                ->with('attachments')
                ->where('space_id', Space::personalLibraryFor($user)->id)
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Item $item) => Payload::item($item));
        }

        if ($user->hasRole(Role::Admin)) {
            $props['stats'] = [
                'teachers' => User::query()->where('role', Role::Teacher)->count(),
                'students' => User::query()->where('role', Role::Student)->count(),
                'courses' => Space::query()->courses()->count(),
            ];
        }

        return Inertia::render('Home', $props);
    }
}
