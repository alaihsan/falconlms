<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Everything Falcon serves comes from its own server (no CDN), so the
     * Content Security Policy can be strict. It is only sent in production
     * because the Vite dev server injects scripts from another port.
     */
    private const string CONTENT_SECURITY_POLICY = "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
        ."img-src 'self' data: blob:; media-src 'self' blob:; font-src 'self' data:; connect-src 'self'; "
        ."frame-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Frame-Options', 'SAMEORIGIN', false);
        $headers->set('X-Content-Type-Options', 'nosniff', false);
        $headers->set('Referrer-Policy', 'same-origin', false);
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()', false);

        if (app()->isProduction()) {
            // File downloads set their own, stricter policy (see AttachmentController).
            $headers->set('Content-Security-Policy', self::CONTENT_SECURITY_POLICY, false);

            if ($request->isSecure()) {
                $headers->set('Strict-Transport-Security', 'max-age=31536000', false);
            }
        }

        return $response;
    }
}
