<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isInternal = empty($user->client_id);
        $logoAssetByClient = $this->activeLogoAssetMap();

        if ($isInternal) {
            $clients = DB::connection('db1')
                ->table('clients')
                ->select('id', 'name', 'type', 'is_active', 'created_at')
                ->orderBy('name')
                ->get()
                ->map(function (object $client): array {
                    $clientId = (string) $client->id;

                    $usersCount = (int) DB::connection('db1')->table('users')->where('client_id', $clientId)->count();
                    $sitesCount = (int) DB::connection('db1')->table('sites')->where('client_id', $clientId)->where('is_active', true)->count();
                    $modulesCount = (int) DB::connection('db2')->table('modules')->where('client_id', $clientId)->where('is_active', true)->count();
                    $responsesCount = (int) DB::connection('db2')->table('responses')->where('client_id', $clientId)->where('is_current', true)->count();
                    $riskCount = (int) DB::connection('db2')->table('responses')
                        ->where('client_id', $clientId)
                        ->where('is_current', true)
                        ->whereIn('status', ['PARCIAL', 'NAO_CONFORME'])
                        ->count();

                    return [
                        'id' => $clientId,
                        'name' => (string) $client->name,
                        'type' => (string) $client->type,
                        'is_active' => (bool) $client->is_active,
                        'users_count' => $usersCount,
                        'sites_count' => $sitesCount,
                        'modules_count' => $modulesCount,
                        'responses_count' => $responsesCount,
                        'risk_count' => $riskCount,
                        'has_logo' => isset($logoAssetByClient[$clientId]),
                        'logo_url' => isset($logoAssetByClient[$clientId]) ? route('client.logo.show', ['clientId' => $clientId]) : null,
                        'created_at' => (string) $client->created_at,
                    ];
                })
                ->values();

            $summary = [
                'clients_total' => $clients->count(),
                'clients_active' => $clients->where('is_active', true)->count(),
                'total_risks' => $clients->sum('risk_count'),
                'total_responses' => $clients->sum('responses_count'),
            ];

            return Inertia::render('Dashboard', [
                'isInternal' => true,
                'summary' => $summary,
                'clients' => $clients,
            ]);
        }

        $clientId = (string) $user->client_id;
        $client = DB::connection('db1')
            ->table('clients')
            ->where('id', $clientId)
            ->first(['id', 'name', 'type', 'is_active', 'created_at']);

        $summary = [
            'clients_total' => 1,
            'clients_active' => $client && $client->is_active ? 1 : 0,
            'total_risks' => (int) DB::connection('db2')->table('responses')
                ->where('client_id', $clientId)
                ->where('is_current', true)
                ->whereIn('status', ['PARCIAL', 'NAO_CONFORME'])
                ->count(),
            'total_responses' => (int) DB::connection('db2')->table('responses')
                ->where('client_id', $clientId)
                ->where('is_current', true)
                ->count(),
        ];

        return Inertia::render('Dashboard', [
            'isInternal' => false,
            'summary' => $summary,
            'clients' => $client ? [[
                'id' => (string) $client->id,
                'name' => (string) $client->name,
                'type' => (string) $client->type,
                'is_active' => (bool) $client->is_active,
                'users_count' => (int) DB::connection('db1')->table('users')->where('client_id', $clientId)->count(),
                'sites_count' => (int) DB::connection('db1')->table('sites')->where('client_id', $clientId)->where('is_active', true)->count(),
                'modules_count' => (int) DB::connection('db2')->table('modules')->where('client_id', $clientId)->where('is_active', true)->count(),
                'responses_count' => $summary['total_responses'],
                'risk_count' => $summary['total_risks'],
                'has_logo' => isset($logoAssetByClient[$clientId]),
                'logo_url' => isset($logoAssetByClient[$clientId]) ? route('client.logo.show', ['clientId' => $clientId]) : null,
                'created_at' => (string) $client->created_at,
            ]] : [],
        ]);
    }

    private function activeLogoAssetMap(): array
    {
        try {
            return DB::connection('db_assets')
                ->table('media_assets')
                ->where('owner_type', 'client')
                ->where('asset_type', 'logo')
                ->where('is_active', true)
                ->select('owner_id')
                ->orderByDesc('id')
                ->get()
                ->pluck('owner_id')
                ->flip()
                ->map(fn () => true)
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
