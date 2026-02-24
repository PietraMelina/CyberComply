<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DB1\BugReport;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BugReportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:3000'],
            'severity' => ['required', 'in:LOW,MEDIUM,HIGH,CRITICAL'],
            'page_url' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        $bug = BugReport::create([
            'reporter_user_id' => $user->id,
            'reporter_email' => $user->email,
            'client_id' => $user->client_id,
            'title' => strip_tags((string) $payload['title']),
            'description' => strip_tags((string) $payload['description']),
            'severity' => (string) $payload['severity'],
            'page_url' => isset($payload['page_url']) ? (string) $payload['page_url'] : null,
            'user_agent' => $request->userAgent(),
            'status' => 'OPEN',
        ]);

        AuditLogger::log('BUG_REPORTED', 'bug_report', (string) $bug->id, null, $bug->toArray(), $user->client_id);

        return back()->with('status', 'Bug reportado com sucesso. Obrigado pelo feedback.');
    }
}
