<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TermsController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Terms/Show', [
            'currentVersion' => (string) config('app.terms_current_version', env('TERMS_CURRENT_VERSION', 'v1')),
            'alreadyAccepted' => (bool) $request->user()?->accepted_terms_at,
        ]);
    }

    public function accept(Request $request): RedirectResponse
    {
        $request->validate([
            'accepted' => ['accepted'],
        ]);

        $user = $request->user();
        $before = $user->toArray();

        $user->update([
            'accepted_terms_at' => now(),
            'accepted_terms_version' => (string) config('app.terms_current_version', env('TERMS_CURRENT_VERSION', 'v1')),
        ]);

        AuditLogger::log('TERMS_ACCEPTED', 'user', $user->id, $before, $user->fresh()->toArray(), $user->client_id);

        return redirect()->route('dashboard')->with('status', 'Termos aceites com sucesso.');
    }
}
