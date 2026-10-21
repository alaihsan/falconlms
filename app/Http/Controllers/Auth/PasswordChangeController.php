<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PasswordChangeController extends Controller
{
    /**
     * Show the mandatory password change form.
     */
    public function show(): Response
    {
        return Inertia::render('Auth/ChangePassword');
    }

    /**
     * Update the user's password and clear must_change_password flag.
     */
    public function update(PasswordChangeRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $user->forceFill([
            'password' => Hash::make($request->string('password')->value()),
            'must_change_password' => false,
        ])->save();

        return redirect()->route('dashboard')->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
