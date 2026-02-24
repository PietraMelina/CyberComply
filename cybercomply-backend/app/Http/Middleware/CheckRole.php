<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->role) {
            abort(403, 'Role not configured.');
        }

        if (!in_array($user->role->name, $roles, true)) {
            abort(403, 'Permission denied.');
        }

        return $next($request);
    }
}
