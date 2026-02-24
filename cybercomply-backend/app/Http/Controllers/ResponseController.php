<?php

namespace App\Http\Controllers;

use App\Models\DB2\Question;
use App\Models\DB2\Response;
use App\Services\AuditLogger;
use App\Services\TenantAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResponseController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $clientId = $this->resolveClientId($request);

        $payload = $request->validate([
            'question_id' => ['required', 'integer'],
            'site_id' => ['nullable', 'integer'],
            'status' => ['required', 'in:CONFORME,PARCIAL,NAO_CONFORME,NAO_APLICA'],
            'comment' => ['nullable', 'string'],
        ]);

        if ($payload['status'] === 'NAO_CONFORME' && empty($payload['comment'])) {
            abort(422, 'comment is required when status is NAO_CONFORME.');
        }

        $question = Question::query()
            ->where('client_id', $clientId)
            ->findOrFail((int) $payload['question_id']);

        if (!empty($payload['site_id'])) {
            $siteExists = DB::connection('db1')->table('sites')
                ->where('id', $payload['site_id'])
                ->where('client_id', $clientId)
                ->exists();

            if (!$siteExists) {
                abort(422, 'Invalid site_id for this client.');
            }
        }

        $newResponse = DB::connection('db2')->transaction(function () use ($payload, $clientId, $request, $question) {
            $currentQuery = Response::query()
                ->where('client_id', $clientId)
                ->where('question_id', $question->id)
                ->where('is_current', true);

            if (array_key_exists('site_id', $payload) && $payload['site_id']) {
                $currentQuery->where('site_id', $payload['site_id']);
            } else {
                $currentQuery->whereNull('site_id');
            }

            $current = $currentQuery->lockForUpdate()->first();

            $new = Response::create([
                'client_id' => $clientId,
                'question_id' => $question->id,
                'site_id' => $payload['site_id'] ?? null,
                'version' => $current ? ($current->version + 1) : 1,
                'status' => $payload['status'],
                'comment' => isset($payload['comment']) ? strip_tags($payload['comment']) : null,
                'answered_by' => $request->user()->id,
                'answered_at' => now(),
                'previous_version_id' => $current?->id,
                'is_current' => true,
            ]);

            if ($current) {
                $current->update(['is_current' => false]);
            }

            return [$new, $current];
        });

        [$response, $previous] = $newResponse;

        $this->audit(
            'RESPONSE_CREATED',
            'response',
            $response->id,
            $previous?->toArray(),
            $response->toArray(),
            $clientId
        );

        return response()->json($response, 201);
    }

    public function history(Request $request, int $id): JsonResponse
    {
        $clientId = $this->resolveClientId($request);

        $response = Response::query()->where('client_id', $clientId)->findOrFail($id);

        $history = Response::query()
            ->where('client_id', $clientId)
            ->where('question_id', $response->question_id)
            ->when($response->site_id, fn ($q) => $q->where('site_id', $response->site_id), fn ($q) => $q->whereNull('site_id'))
            ->orderByDesc('version')
            ->get();

        return response()->json($history);
    }

    public function evidences(Request $request, int $id): JsonResponse
    {
        $clientId = $this->resolveClientId($request);

        $response = Response::query()
            ->with('evidences')
            ->where('client_id', $clientId)
            ->findOrFail($id);

        return response()->json($response->evidences);
    }

    private function resolveClientId(Request $request): string
    {
        $user = $request->user();

        if ($user->client_id) {
            return $user->client_id;
        }

        $clientId = (string) ($request->input('client_id') ?? $request->query('client_id', ''));
        if ($clientId === '') {
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
