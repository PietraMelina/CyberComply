<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantIsolation
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->client_id) {
            return $next($request);
        }

        $requestedClientId = $request->route('client')
            ?? $request->route('client_id')
            ?? $request->input('client_id');

        if ($requestedClientId && $requestedClientId !== $user->client_id) {
            AuditLogger::log(
                'PERMISSION_DENIED',
                'tenant',
                (string) $requestedClientId,
                null,
                ['reason' => 'tenant_mismatch'],
                $user->client_id
            );

            abort(403, 'Tenant isolation violation.');
        }

        return $next($request);
    }
}
