<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogPageController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'source' => ['nullable', 'in:all,db1,db2'],
            'user_id' => ['nullable', 'string', 'max:11'],
            'client_id' => ['nullable', 'string', 'max:14'],
            'action' => ['nullable', 'string', 'max:50'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'selected_id' => ['nullable', 'integer', 'min:1'],
            'selected_source' => ['nullable', 'in:db1,db2'],
        ]);

        $perPage = (int) ($filters['per_page'] ?? 20);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $rows = $this->mergedLogs($filters, $request->user()->client_id);
        $total = $rows->count();
        $items = $rows->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('AuditLogs/Index', [
            'filters' => [
                'source' => $filters['source'] ?? 'all',
                'user_id' => $filters['user_id'] ?? '',
                'client_id' => $filters['client_id'] ?? '',
                'action' => $filters['action'] ?? '',
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
                'per_page' => $perPage,
            ],
            'logs' => [
                'data' => $items,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $paginator->lastPage(),
            ],
            'summary' => $this->summary($rows),
            'selectedLog' => $this->selectedLog($filters, $request->user()->client_id),
        ]);
    }

    private function summary(Collection $rows): array
    {
        $criticalActions = ['PERMISSION_DENIED', 'ROLE_CHANGED', 'EXPORT_GENERATED', 'CLIENT_DEACTIVATED', 'USER_DEACTIVATED'];
        $actionsCount = $rows->groupBy('action')->map(fn ($items) => $items->count())->sortDesc()->take(5);
        $first = $rows->last();
        $last = $rows->first();

        return [
            'total_events' => $rows->count(),
            'unique_users' => $rows->pluck('user_id')->filter()->unique()->count(),
            'critical_actions' => $rows->filter(fn ($r) => in_array($r['action'], $criticalActions, true))->count(),
            'period' => $first && $last ? substr($first['created_at'], 0, 10).' a '.substr($last['created_at'], 0, 10) : '-',
            'top_actions' => $actionsCount,
        ];
    }

    private function mergedLogs(array $filters, ?string $userClientId): Collection
    {
        $source = $filters['source'] ?? 'all';
        $rows = collect();

        if ($source === 'all' || $source === 'db1') {
            $rows = $rows->concat($this->queryLogs('db1', $filters, $userClientId)->get()->map(function ($row) {
                $row->source = 'db1';

                return $this->formatLog($row);
            }));
        }

        if ($source === 'all' || $source === 'db2') {
            $rows = $rows->concat($this->queryLogs('db2', $filters, $userClientId)->get()->map(function ($row) {
                $row->source = 'db2';

                return $this->formatLog($row);
            }));
        }

        return $rows->sortByDesc(fn ($row) => sprintf('%s|%020d', $row['created_at'], $row['id']))->values();
    }

    private function queryLogs(string $connection, array $filters, ?string $userClientId)
    {
        $query = DB::connection($connection)
            ->table('audit_logs')
            ->when(!empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['client_id']), fn ($q) => $q->where('client_id', $filters['client_id']))
            ->when(!empty($filters['action']), fn ($q) => $q->where('action', $filters['action']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->where('created_at', '>=', $filters['date_from'].' 00:00:00'))
            ->when(!empty($filters['date_to']), fn ($q) => $q->where('created_at', '<=', $filters['date_to'].' 23:59:59'))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($userClientId) {
            $query->where('client_id', $userClientId);
        }

        return $query;
    }

    private function selectedLog(array $filters, ?string $userClientId): ?array
    {
        $selectedId = $filters['selected_id'] ?? null;
        $selectedSource = $filters['selected_source'] ?? null;

        if (!$selectedId || !$selectedSource) {
            return null;
        }

        $query = DB::connection($selectedSource)->table('audit_logs')->where('id', $selectedId);
        if ($userClientId) {
            $query->where('client_id', $userClientId);
        }

        $row = $query->first();
        if (!$row) {
            return null;
        }

        $row->source = $selectedSource;
        $formatted = $this->formatLog($row);
        $formatted['diff'] = $this->computeDiff($formatted['before_state'], $formatted['after_state']);

        return $formatted;
    }

    private function formatLog(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'source' => (string) $row->source,
            'log_id' => (string) $row->log_id,
            'user_id' => (string) $row->user_id,
            'client_id' => $row->client_id ? (string) $row->client_id : null,
            'action' => (string) $row->action,
            'entity_type' => (string) $row->entity_type,
            'entity_id' => (string) $row->entity_id,
            'request_id' => $row->request_id ? (string) $row->request_id : null,
            'ip_address' => (string) $row->ip_address,
            'created_at' => (string) $row->created_at,
            'before_state' => $this->decodeJson($row->before_state),
            'after_state' => $this->decodeJson($row->after_state),
            'changes_summary' => $this->decodeJson($row->changes_summary),
        ];
    }

    private function decodeJson($value): array|null
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        $decoded = json_decode((string) $value, true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function computeDiff(?array $before, ?array $after): array
    {
        if (!$before || !$after) {
            return [];
        }

        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $diff = [];

        foreach ($keys as $key) {
            $beforeValue = $before[$key] ?? null;
            $afterValue = $after[$key] ?? null;

            if ($beforeValue !== $afterValue) {
                $diff[$key] = [
                    'from' => $beforeValue,
                    'to' => $afterValue,
                ];
            }
        }

        return $diff;
    }
}
