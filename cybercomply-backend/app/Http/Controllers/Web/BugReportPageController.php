<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DB1\BugReport;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BugReportPageController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:OPEN,IN_PROGRESS,RESOLVED'],
            'severity' => ['nullable', 'in:LOW,MEDIUM,HIGH,CRITICAL'],
            'q' => ['nullable', 'string', 'max:150'],
            'client_id' => ['nullable', 'string', 'size:14'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $user = $request->user();
        $query = BugReport::query()->orderByDesc('created_at');

        if ($user->client_id) {
            $query->where('client_id', $user->client_id);
        } else {
            $clientId = $this->resolveInternalClientId($request, $filters['client_id'] ?? null);
            if ($clientId !== '') {
                $query->where('client_id', $clientId);
            }
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['q'])) {
            $search = trim((string) $filters['q']);
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('reporter_email', 'like', "%{$search}%")
                    ->orWhere('reporter_user_id', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        $bugs = $query->paginate($perPage)->withQueryString();

        return Inertia::render('BugReports/Index', [
            'bugs' => $bugs,
            'filters' => [
                'status' => $filters['status'] ?? '',
                'severity' => $filters['severity'] ?? '',
                'q' => $filters['q'] ?? '',
                'per_page' => $perPage,
                'client_id' => $filters['client_id'] ?? '',
            ],
            'canUpdateStatus' => in_array((string) $user->role?->name, ['MASTER', 'GESTOR', 'ADMIN_CLIENTE'], true),
        ]);
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'in:OPEN,IN_PROGRESS,RESOLVED'],
        ]);

        $user = $request->user();
        $bug = BugReport::query()->findOrFail($id);

        if ($user->client_id && $bug->client_id !== $user->client_id) {
            abort(403, 'Tenant isolation violation.');
        }

        $before = $bug->toArray();
        $bug->status = (string) $payload['status'];
        $bug->save();

        AuditLogger::log('BUG_STATUS_UPDATED', 'bug_report', (string) $bug->id, $before, $bug->toArray(), $bug->client_id);

        return back()->with('status', 'Status do bug atualizado com sucesso.');
    }

    private function resolveInternalClientId(Request $request, ?string $requested): string
    {
        if (!empty($requested)) {
            $request->session()->put('selected_internal_client_id', $requested);

            return $requested;
        }

        return (string) $request->session()->get('selected_internal_client_id', '');
    }
}
