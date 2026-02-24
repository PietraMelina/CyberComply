<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCmdValidated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('cmd_identity')) {
            return redirect()->route('login')->with('status', 'Validação CMD necessária.');
        }

        return $next($request);
    }
}

