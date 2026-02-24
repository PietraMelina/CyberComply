<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\TenantAuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $this->validatedFilters($request);
        $user = $request->user();

        $rows = $this->mergedLogs($filters, $user->client_id, true);

        $perPage = (int) ($filters['per_page'] ?? 20);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $total = $rows->count();

        $items = $rows->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json($paginator);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $filters = $this->validatedFilters($request);
        $source = $filters['source'] ?? 'all';

        $userClientId = $request->user()->client_id;

        $log = null;
        if ($source === 'db1' || $source === 'all') {
            $query = DB::connection('db1')->table('audit_logs')->where('id', $id);
            if ($userClientId) {
                $query->where('client_id', $userClientId);
            }
            $log = $query->first();
            if ($log) {
                $log->source = 'db1';
            }
        }

        if (!$log && ($source === 'db2' || $source === 'all')) {
            $query = DB::connection('db2')->table('audit_logs')->where('id', $id);
            if ($userClientId) {
                $query->where('client_id', $userClientId);
            }
            $log = $query->first();
            if ($log) {
                $log->source = 'db2';
            }
        }

        if (!$log) {
            abort(404, 'Audit log not found.');
        }

        return response()->json($log);
    }

    public function export(Request $request): StreamedResponse|Response
    {
        $filters = $this->validatedFilters($request);
        $format = (string) $request->input('format', 'csv');
        $rows = $this->mergedLogs($filters, $request->user()->client_id, false);

        $this->auditExport($request, $rows->count(), $format);

        if ($format === 'pdf') {
            $fileName = 'audit_logs_'.date('Ymd_His').'.pdf';
            $integrityHash = $this->generateIntegrityHash($rows);
            $pdf = Pdf::loadView('pdf.audit-report', [
                'logs' => $rows,
                'generated_by' => (string) $request->user()->id,
                'generated_at' => now()->toDateTimeString(),
                'integrity_hash' => $integrityHash,
            ])->setPaper('a4', 'portrait');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            ]);
        }

        $fileName = 'audit_logs_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, [
                'source', 'id', 'log_id', 'created_at', 'user_id', 'client_id', 'action', 'entity_type', 'entity_id',
                'ip_address', 'request_id',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->source,
                    $row->id,
                    $row->log_id,
                    $row->created_at,
                    $row->user_id,
                    $row->client_id,
                    $row->action,
                    $row->entity_type,
                    $row->entity_id,
                    $row->ip_address,
                    $row->request_id,
                ]);
            }

            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'format' => ['nullable', 'in:csv,pdf'],
            'source' => ['nullable', 'in:all,db1,db2'],
            'user_id' => ['nullable', 'string', 'max:11'],
            'client_id' => ['nullable', 'string', 'max:14'],
            'action' => ['nullable', 'string', 'max:50'],
            'entity_type' => ['nullable', 'string', 'max:50'],
            'entity_id' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    private function mergedLogs(array $filters, ?string $userClientId, bool $applySoftPageLimit): Collection
    {
        $source = $filters['source'] ?? 'all';

        $collections = collect();

        if ($source === 'all' || $source === 'db1') {
            $db1 = $this->queryLogs('db1', $filters, $userClientId, $applySoftPageLimit)->get()->map(function ($row) {
                $row->source = 'db1';

                return $row;
            });
            $collections = $collections->concat($db1);
        }

        if ($source === 'all' || $source === 'db2') {
            $db2 = $this->queryLogs('db2', $filters, $userClientId, $applySoftPageLimit)->get()->map(function ($row) {
                $row->source = 'db2';

                return $row;
            });
            $collections = $collections->concat($db2);
        }

        return $collections->sortByDesc(fn ($row) => sprintf('%s|%020d', $row->created_at, $row->id))->values();
    }

    private function queryLogs(string $connection, array $filters, ?string $userClientId, bool $applySoftPageLimit)
    {
        $query = DB::connection($connection)
            ->table('audit_logs')
            ->when(!empty($filters['user_id']), fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when(!empty($filters['client_id']), fn ($q) => $q->where('client_id', $filters['client_id']))
            ->when(!empty($filters['action']), fn ($q) => $q->where('action', $filters['action']))
            ->when(!empty($filters['entity_type']), fn ($q) => $q->where('entity_type', $filters['entity_type']))
            ->when(!empty($filters['entity_id']), fn ($q) => $q->where('entity_id', $filters['entity_id']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->where('created_at', '>=', $filters['date_from'].' 00:00:00'))
            ->when(!empty($filters['date_to']), fn ($q) => $q->where('created_at', '<=', $filters['date_to'].' 23:59:59'))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($userClientId) {
            $query->where('client_id', $userClientId);
        }

        if ($applySoftPageLimit) {
            $page = max(1, (int) ($filters['page'] ?? 1));
            $perPage = (int) ($filters['per_page'] ?? 20);
            $query->limit(max(100, $page * $perPage * 3));
        }

        return $query;
    }

    private function auditExport(Request $request, int $totalRows, string $format): void
    {
        $after = ['exported_rows' => $totalRows, 'format' => $format];
        AuditLogger::log('EXPORT_GENERATED', 'audit_log', $format, null, $after, $request->user()->client_id);
        TenantAuditLogger::log('EXPORT_GENERATED', 'audit_log', $format, null, $after, $request->user()->client_id);
    }

    private function generateIntegrityHash(Collection $rows): string
    {
        $payload = $rows->map(function ($row): array {
            return [
                'source' => $row->source,
                'id' => $row->id,
                'log_id' => $row->log_id,
                'created_at' => $row->created_at,
                'user_id' => $row->user_id,
                'client_id' => $row->client_id,
                'action' => $row->action,
                'entity_type' => $row->entity_type,
                'entity_id' => $row->entity_id,
                'ip_address' => $row->ip_address,
                'request_id' => $row->request_id,
            ];
        })->values()->toJson();

        return hash('sha256', $payload);
    }
}
