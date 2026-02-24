<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMfaEnrollment
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!(bool) env('MFA_ENFORCE_ENROLLMENT', true)) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        $user->loadMissing('role');
        $role = (string) ($user->role?->name ?? '');

        if ($role === 'MASTER') {
            return $next($request);
        }

        if ($user->mfa_enabled) {
            return $next($request);
        }

        if ($request->routeIs('security.mfa.show', 'security.mfa.enable', 'logout')) {
            return $next($request);
        }

        return redirect()->route('security.mfa.show')
            ->with('status', 'Configure o MFA para continuar a usar a plataforma.');
    }
}
