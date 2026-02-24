<?php

namespace App\Http\Controllers;

use App\Models\DB2\Module;
use App\Models\DB2\Question;
use App\Models\DB2\Response;
use App\Services\AuditLogger;
use App\Services\TenantAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clientId = $this->resolveClientId($request);

        $modules = Module::query()
            ->where('client_id', $clientId)
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 15));

        return response()->json($modules);
    }

    public function store(Request $request): JsonResponse
    {
        $clientId = $this->resolveClientId($request);

        $payload = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'version' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $module = Module::create([
            'client_id' => $clientId,
            'code' => $payload['code'],
            'name' => $payload['name'],
            'description' => isset($payload['description']) ? strip_tags($payload['description']) : null,
            'version' => $payload['version'] ?? 1,
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $this->audit('MODULE_CREATED', 'module', $module->id, null, $module->toArray(), $clientId);

        return response()->json($module, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $clientId = $this->resolveClientId($request);

        $module = Module::query()
            ->with('questions')
            ->where('client_id', $clientId)
            ->findOrFail($id);

        return response()->json($module);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $clientId = $this->resolveClientId($request);

        $module = Module::query()->where('client_id', $clientId)->findOrFail($id);
        $before = $module->toArray();

        $payload = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        if (array_key_exists('description', $payload) && $payload['description'] !== null) {
            $payload['description'] = strip_tags($payload['description']);
        }

        $module->fill($payload);
        $module->save();

        $this->audit('MODULE_UPDATED', 'module', $module->id, $before, $module->fresh()->toArray(), $clientId);

        return response()->json($module->fresh());
    }

    public function storeQuestion(Request $request, int $id): JsonResponse
    {
        $clientId = $this->resolveClientId($request);

        $module = Module::query()->where('client_id', $clientId)->findOrFail($id);

        $payload = $request->validate([
            'question_text' => ['required', 'string'],
            'weight' => ['nullable', 'numeric', 'min:0.01', 'max:999.99'],
            'order_index' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $question = Question::create([
            'module_id' => $module->id,
            'client_id' => $clientId,
            'question_text' => strip_tags($payload['question_text']),
            'weight' => $payload['weight'] ?? 1.0,
            'order_index' => $payload['order_index'],
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'created_at' => now(),
        ]);

        $this->audit('QUESTION_CREATED', 'question', $question->id, null, $question->toArray(), $clientId);

        return response()->json($question, 201);
    }

    public function responses(Request $request, int $id): JsonResponse
    {
        $clientId = $this->resolveClientId($request);

        $module = Module::query()->where('client_id', $clientId)->findOrFail($id);

        $responses = Response::query()
            ->where('client_id', $clientId)
            ->whereHas('question', fn ($q) => $q->where('module_id', $module->id))
            ->with(['question', 'evidences'])
            ->orderByDesc('answered_at')
            ->paginate((int) $request->integer('per_page', 15));

        return response()->json($responses);
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
