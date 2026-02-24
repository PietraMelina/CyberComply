<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DB1\Site;
use App\Models\DB2\Response as TenantResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResponsePageController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $siteId = $request->input('site_id');

        $sitesQuery = Site::query()->orderBy('name');
        if ($user->client_id) {
            $sitesQuery->where('client_id', $user->client_id);
        } elseif ($request->filled('client_id')) {
            $sitesQuery->where('client_id', (string) $request->input('client_id'));
        }
        $sites = $sitesQuery->get(['id', 'name', 'address_id', 'client_id']);
        $siteIds = $sites->pluck('id')->all();
        $siteNames = $sites->pluck('name', 'id');

        $responsesQuery = TenantResponse::query()
            ->with(['question.module', 'evidences'])
            ->where('is_current', true)
            ->orderByDesc('answered_at');

        if ($user->client_id) {
            $responsesQuery->where('client_id', $user->client_id);
        } else {
            $clientId = $this->resolveInternalClientId($request);
            if ($clientId !== '') {
                $responsesQuery->where('client_id', $clientId);
            }
        }

        if ($siteId !== null && $siteId !== '') {
            $responsesQuery->where('site_id', (int) $siteId);
        } elseif (!empty($siteIds)) {
            $responsesQuery->where(function ($q) use ($siteIds): void {
                $q->whereIn('site_id', $siteIds)->orWhereNull('site_id');
            });
        }

        $responses = $responsesQuery->paginate((int) $request->integer('per_page', 20));
        $responses->getCollection()->transform(function (TenantResponse $item) use ($siteNames): array {
            $data = $item->toArray();
            $data['site_name'] = $item->site_id ? ($siteNames[$item->site_id] ?? 'Site removido') : 'Global';
            $data['module_code'] = $item->question?->module?->code;
            $data['question_text'] = $item->question?->question_text;

            return $data;
        });

        return Inertia::render('Responses/Index', [
            'responses' => $responses,
            'sites' => $sites,
            'selectedSite' => $siteId ? (string) $siteId : '',
        ]);
    }

    private function resolveInternalClientId(Request $request): string
    {
        $requested = (string) $request->input('client_id', '');
        if ($requested !== '') {
            $request->session()->put('selected_internal_client_id', $requested);

            return $requested;
        }

        return (string) $request->session()->get('selected_internal_client_id', '');
    }
}
