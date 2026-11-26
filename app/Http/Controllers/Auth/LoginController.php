<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Seeded demo accounts offered as one-click sign-in on a local machine only.
     *
     * @var list<array{label: string, login: string, password: string}>
     */
    private const array DEMO_ACCOUNTS = [
        ['label' => 'Admin', 'login' => 'admin@falcon.sch.id', 'password' => 'admin123'],
        ['label' => 'Guru', 'login' => 'budi@falcon.sch.id', 'password' => 'guru123'],
        ['label' => 'Siswa', 'login' => 'fauzi@falcon.sch.id', 'password' => 'siswa123'],
    ];

    /**
     * Display the login view.
     */
    public function create(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login', [
            'demoAccounts' => app()->isLocal() ? self::DEMO_ACCOUNTS : [],
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user && $user->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
