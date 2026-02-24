<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTermsAcceptance
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        if ($request->routeIs('terms.show', 'terms.accept', 'logout')) {
            return $next($request);
        }

        if ($user->accepted_terms_at) {
            return $next($request);
        }

        return redirect()->route('terms.show')
            ->with('status', 'É necessário aceitar os Termos e Condições para continuar.');
    }
}
