<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the change password page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('auth/ChangePassword', [
            'mustChangePassword' => $request->user()->must_change_password,
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->password,
            'must_change_password' => false,
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return redirect()->route('home');
    }
}
