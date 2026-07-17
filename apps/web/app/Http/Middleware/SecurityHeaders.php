<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defense-in-depth alongside infrastructure/nginx/default.conf — these
 * headers should be set at the reverse proxy in production too, but
 * setting them here means the app is still safe if it's ever run
 * behind a different proxy (or none) than the one shipped in this repo.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Only set HSTS when the request actually arrived over HTTPS —
        // setting it on plain HTTP (e.g. local dev without a TLS-terminating
        // proxy) would tell browsers to force HTTPS on a host that doesn't
        // serve it yet, breaking local access.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
