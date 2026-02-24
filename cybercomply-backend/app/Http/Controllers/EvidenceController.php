<?php

namespace App\Http\Controllers;

use App\Models\DB2\Evidence;
use App\Models\DB2\Response;
use App\Services\AuditLogger;
use App\Services\EvidenceStorage;
use App\Services\TenantAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EvidenceController extends Controller
{
    public function __construct(private readonly EvidenceStorage $evidenceStorage)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $clientId = $this->resolveClientId($request, true);

        $payload = $request->validate([
            'response_id' => ['required', 'integer'],
            'file' => ['required', 'file'],
        ]);

        $response = Response::query()
            ->where('client_id', $clientId)
            ->findOrFail((int) $payload['response_id']);

        $evidence = $this->evidenceStorage->store(
            $request->file('file'),
            $response,
            $request->user()->id,
            $clientId
        );

        $this->audit('EVIDENCE_UPLOADED', 'evidence', $evidence->id, null, $evidence->toArray(), $clientId);

        return response()->json($evidence, 201);
    }

    public function download(Request $request, string $token): BinaryFileResponse
    {
        $clientId = $this->resolveClientId($request);

        $query = Evidence::query()->where('internal_token', $token);

        if ($request->user()->client_id) {
            $query->where('client_id', $clientId);
        }

        $evidence = $query->firstOrFail();

        if (!is_file($evidence->storage_path)) {
            abort(404, 'File not found in storage.');
        }

        $this->audit('EVIDENCE_DOWNLOADED', 'evidence', $evidence->id, null, ['downloaded' => true], $evidence->client_id);

        return response()->download($evidence->storage_path, $evidence->original_filename);
    }

    private function resolveClientId(Request $request, bool $required = false): string
    {
        $user = $request->user();

        if ($user->client_id) {
            return $user->client_id;
        }

        $clientId = (string) ($request->input('client_id') ?? $request->query('client_id', ''));
        if ($required && $clientId === '') {
            abort(422, 'client_id is required for internal users.');
        }

        return $clientId;
    }

    private function audit(string $action, string $entityType, int|string $entityId, ?array $before, ?array $after, string $clientId): void
    {
        AuditLogger::log($action, $entityType, $entityId, $before, $after, $clientId);
        TenantAuditLogger::log($action, $entityType, $entityId, $before, $after, $clientId);
    }
}
