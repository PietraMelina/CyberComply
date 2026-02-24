<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DB1\Site;
use App\Models\DB2\Module;
use App\Models\DB2\Question;
use App\Models\DB2\Response as TenantResponse;
use App\Services\AuditLogger;
use App\Services\TenantAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ModulePageController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = $this->resolveClientId($request);

        $modules = Module::query()
            ->where('client_id', $clientId)
            ->orderBy('code')
            ->paginate((int) $request->integer('per_page', 20));

        return Inertia::render('Modules/Index', [
            'modules' => $modules,
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        $clientId = $this->resolveClientId($request);
        $siteId = $request->query('site_id');

        $module = Module::query()
            ->where('client_id', $clientId)
            ->with(['questions' => fn ($q) => $q->where('is_active', true)->orderBy('order_index')])
            ->findOrFail($id);

        $sites = Site::query()
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedSiteId = null;
        if ($siteId !== null && $siteId !== '') {
            $selectedSiteId = (int) $siteId;
            $siteIsValid = $sites->contains(fn ($s) => (int) $s->id === $selectedSiteId);
            if (!$siteIsValid) {
                abort(422, 'Invalid site_id for current client.');
            }
        }

        $questionIds = $module->questions->pluck('id')->all();
        $responsesQuery = TenantResponse::query()
            ->where('client_id', $clientId)
            ->where('is_current', true)
            ->whereIn('question_id', $questionIds)
            ->with('evidences')
            ->orderByDesc('answered_at');

        if ($selectedSiteId) {
            $responsesQuery->where('site_id', $selectedSiteId);
        } else {
            $responsesQuery->whereNull('site_id');
        }

        $responses = $responsesQuery->get()->keyBy('question_id');

        $questions = $module->questions->map(function (Question $question) use ($responses): array {
            $response = $responses->get($question->id);

            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'weight' => $question->weight,
                'order_index' => $question->order_index,
                'current_response' => $response ? [
                    'id' => $response->id,
                    'status' => $response->status,
                    'comment' => $response->comment,
                    'version' => $response->version,
                    'answered_at' => $response->answered_at,
                    'evidences' => $response->evidences->map(fn ($e) => [
                        'id' => $e->id,
                        'internal_token' => $e->internal_token,
                        'original_filename' => $e->original_filename,
                        'mime_type' => $e->mime_type,
                        'file_size_bytes' => $e->file_size_bytes,
                        'uploaded_at' => $e->uploaded_at,
                    ])->values(),
                ] : null,
            ];
        })->values();

        return Inertia::render('Modules/Show', [
            'module' => [
                'id' => $module->id,
                'code' => $module->code,
                'name' => $module->name,
                'description' => $module->description,
                'version' => $module->version,
            ],
            'sites' => $sites,
            'selectedSiteId' => $selectedSiteId,
            'questions' => $questions,
            'statusOptions' => ['CONFORME', 'PARCIAL', 'NAO_CONFORME', 'NAO_APLICA'],
        ]);
    }

    public function storeResponse(Request $request, int $id): RedirectResponse
    {
        $clientId = $this->resolveClientId($request);

        $module = Module::query()
            ->where('client_id', $clientId)
            ->findOrFail($id);

        $payload = $request->validate([
            'question_id' => ['required', 'integer'],
            'site_id' => ['nullable', 'integer'],
            'status' => ['required', 'in:CONFORME,PARCIAL,NAO_CONFORME,NAO_APLICA'],
            'comment' => ['nullable', 'string'],
        ]);

        if ($payload['status'] === 'NAO_CONFORME' && empty($payload['comment'])) {
            return back()->withErrors(['comment' => 'Comentário é obrigatório para NAO_CONFORME.']);
        }

        $question = Question::query()
            ->where('client_id', $clientId)
            ->where('module_id', $module->id)
            ->findOrFail((int) $payload['question_id']);

        if (!empty($payload['site_id'])) {
            $siteExists = Site::query()
                ->where('client_id', $clientId)
                ->where('id', (int) $payload['site_id'])
                ->exists();

            if (!$siteExists) {
                return back()->withErrors(['site_id' => 'Site inválido para este cliente.']);
            }
        }

        [$newResponse, $previous] = DB::connection('db2')->transaction(function () use ($clientId, $payload, $request, $question): array {
            $currentQuery = TenantResponse::query()
                ->where('client_id', $clientId)
                ->where('question_id', $question->id)
                ->where('is_current', true);

            if (!empty($payload['site_id'])) {
                $currentQuery->where('site_id', (int) $payload['site_id']);
            } else {
                $currentQuery->whereNull('site_id');
            }

            $current = $currentQuery->lockForUpdate()->first();

            $new = TenantResponse::create([
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

        AuditLogger::log(
            'RESPONSE_CREATED',
            'response',
            $newResponse->id,
            $previous?->toArray(),
            $newResponse->toArray(),
            $clientId
        );
        TenantAuditLogger::log(
            'RESPONSE_CREATED',
            'response',
            $newResponse->id,
            $previous?->toArray(),
            $newResponse->toArray(),
            $clientId
        );

        return redirect()
            ->route('modules.show', ['id' => $module->id, 'site_id' => $payload['site_id'] ?? null])
            ->with('status', 'Resposta registrada com nova versão.');
    }

    private function resolveClientId(Request $request): string
    {
        $user = $request->user();

        if ($user->client_id) {
            return $user->client_id;
        }

        $requestedClientId = (string) ($request->input('client_id') ?? $request->query('client_id', ''));
        if ($requestedClientId !== '') {
            $request->session()->put('selected_internal_client_id', $requestedClientId);

            return $requestedClientId;
        }

        $sessionClientId = (string) $request->session()->get('selected_internal_client_id', '');
        if ($sessionClientId !== '') {
            return $sessionClientId;
        }

        $fallbackClientId = (string) DB::connection('db1')
            ->table('clients')
            ->where('is_active', true)
            ->orderBy('created_at')
            ->value('id');

        if ($fallbackClientId === '') {
            abort(422, 'No active client available for internal users.');
        }

        $request->session()->put('selected_internal_client_id', $fallbackClientId);

        return $fallbackClientId;
    }
}
