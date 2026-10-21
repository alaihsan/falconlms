<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard / home screen.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $stats = match ($user?->role) {
            UserRole::Admin => [
                'total_users' => User::count(),
                'total_guru' => User::where('role', UserRole::Guru)->count(),
                'total_siswa' => User::where('role', UserRole::Siswa)->count(),
            ],
            UserRole::Guru => [
                'total_classes' => 0,
                'total_items' => 0,
            ],
            UserRole::Siswa => [
                'enrolled_classes' => 0,
                'pending_tasks' => 0,
            ],
            default => [],
        };

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }
}
