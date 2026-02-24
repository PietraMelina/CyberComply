<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        if ($user) {
            $user->loadMissing('role');
        }

        $availableClients = [];
        if ($user && empty($user->client_id)) {
            $availableClients = DB::connection('db1')
                ->table('clients')
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(200)
                ->get(['id', 'name', 'type'])
                ->map(fn (object $client): array => [
                    'id' => (string) $client->id,
                    'name' => (string) $client->name,
                    'type' => (string) $client->type,
                ])
                ->values()
                ->all();
        }

        $selectedClientId = $request->session()->get('selected_internal_client_id');
        if (!$selectedClientId && !empty($availableClients)) {
            $selectedClientId = $availableClients[0]['id'];
            $request->session()->put('selected_internal_client_id', $selectedClientId);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'display_name' => $user->display_name,
                    'email' => $user->email,
                    'client_id' => $user->client_id,
                    'role' => $user->role?->name,
                    'mfa_enabled' => (bool) $user->mfa_enabled,
                    'accepted_terms_at' => $user->accepted_terms_at,
                    'avatar_asset_id' => $user->avatar_asset_id,
                    'avatar_url' => $user->avatar_asset_id ? route('profile.avatar.show', ['id' => $user->avatar_asset_id]) : null,
                ] : null,
                'api_token' => $user ? $request->session()->get('api_access_token') : null,
                'available_clients' => $availableClients,
                'selected_client_id' => $selectedClientId,
            ],
        ];
    }
}
