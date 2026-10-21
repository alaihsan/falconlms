<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * Restrict route access to users with one of the authorized roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $allowedRoles = array_map(
            static fn (string $role): string => UserRole::tryFrom($role)?->value ?? $role,
            $roles,
        );

        if (! in_array($user->role->value, $allowedRoles, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Akses tidak diizinkan untuk peran Anda.');
        }

        return $next($request);
    }
}
