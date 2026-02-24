<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DB1\Client;
use App\Models\DB1\ClientAddress;
use App\Models\DB1\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SitePageController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = Site::query()->with('address');

        if ($user->client_id) {
            $query->where('client_id', $user->client_id);
        } else {
            $clientId = $this->resolveInternalClientId($request);
            if ($clientId !== '') {
                $query->where('client_id', $clientId);
            }
        }

        return Inertia::render('Sites/Index', [
            'sites' => $query->orderBy('name')->get(),
            'canCreate' => in_array($user->role?->name, ['MASTER', 'GESTOR', 'ADMIN_CLIENTE'], true),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $clients = [];

        if (!$user->client_id) {
            $clients = Client::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }

        return Inertia::render('Sites/Create', [
            'clients' => $clients,
            'isInternal' => !$user->client_id,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $payload = $request->validate([
            'client_id' => ['nullable', 'string', 'size:14', 'exists:db1.clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $clientId = $user->client_id ?: (string) ($payload['client_id'] ?? '');
        if ($clientId === '') {
            return back()->withErrors(['client_id' => 'client_id é obrigatório para utilizador interno.']);
        }

        DB::connection('db1')->transaction(function () use ($payload, $clientId): void {
            $address = ClientAddress::create([
                'client_id' => $clientId,
                'type' => 'OPERATIONAL',
                'address_line1' => $payload['address_line1'],
                'address_line2' => $payload['address_line2'] ?? null,
                'city' => $payload['city'],
                'postal_code' => $payload['postal_code'],
                'country' => $payload['country'] ?? 'PT',
                'is_primary' => false,
                'created_at' => now(),
            ]);

            Site::create([
                'client_id' => $clientId,
                'name' => $payload['name'],
                'address_id' => $address->id,
                'is_active' => true,
                'created_at' => now(),
            ]);
        });

        return redirect()->route('sites.index')->with('status', 'Site criado com sucesso.');
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
