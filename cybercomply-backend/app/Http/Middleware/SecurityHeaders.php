<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $isLocal = app()->environment('local');
        $scriptSrc = ["'self'", "'unsafe-inline'"];
        $styleSrc = ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'];
        $connectSrc = ["'self'"];

        if ($isLocal) {
            $scriptSrc[] = 'http://127.0.0.1:5173';
            $scriptSrc[] = 'http://localhost:5173';

            $styleSrc[] = 'http://127.0.0.1:5173';
            $styleSrc[] = 'http://localhost:5173';

            $connectSrc[] = 'http://127.0.0.1:5173';
            $connectSrc[] = 'http://localhost:5173';
            $connectSrc[] = 'ws://127.0.0.1:5173';
            $connectSrc[] = 'ws://localhost:5173';
        }

        $csp = implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $scriptSrc),
            'style-src '.implode(' ', $styleSrc),
            "img-src 'self' data: blob: https:",
            "font-src 'self' https://fonts.bunny.net data:",
            'connect-src '.implode(' ', $connectSrc),
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
