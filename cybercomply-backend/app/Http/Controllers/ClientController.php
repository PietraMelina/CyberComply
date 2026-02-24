<?php

namespace App\Http\Controllers;

use App\Models\DB1\Client;
use App\Models\DB1\ClientAddress;
use App\Models\DB1\Site;
use App\Services\AuditLogger;
use App\Services\ClientIdGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function __construct(private readonly ClientIdGenerator $clientIdGenerator)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Client::query();

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->boolean('is_active'));
        }

        return response()->json($query->paginate((int) $request->integer('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'type' => ['required', 'in:PRIV,PUBL'],
            'name' => ['required', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.type' => ['required_with:billing_address', 'in:HEADQUARTERS,BILLING,OPERATIONAL'],
            'billing_address.address_line1' => ['required_with:billing_address', 'string', 'max:255'],
            'billing_address.address_line2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required_with:billing_address', 'string', 'max:100'],
            'billing_address.postal_code' => ['required_with:billing_address', 'string', 'max:20'],
            'billing_address.country' => ['nullable', 'string', 'size:2'],
            'billing_address.is_primary' => ['nullable', 'boolean'],
        ]);

        $client = DB::connection('db1')->transaction(function () use ($payload) {
            $client = Client::create([
                'id' => $this->clientIdGenerator->generate($payload['type'], (int) date('Y')),
                'type' => $payload['type'],
                'name' => $payload['name'],
                'vat_number' => $payload['vat_number'] ?? null,
                'is_active' => true,
            ]);

            if (!empty($payload['billing_address'])) {
                $address = ClientAddress::create([
                    'client_id' => $client->id,
                    'type' => $payload['billing_address']['type'],
                    'address_line1' => $payload['billing_address']['address_line1'],
                    'address_line2' => $payload['billing_address']['address_line2'] ?? null,
                    'city' => $payload['billing_address']['city'],
                    'postal_code' => $payload['billing_address']['postal_code'],
                    'country' => $payload['billing_address']['country'] ?? 'PT',
                    'is_primary' => (bool) ($payload['billing_address']['is_primary'] ?? false),
                    'created_at' => now(),
                ]);

                $client->billing_address_id = $address->id;
                $client->save();
            }

            return $client;
        });

        AuditLogger::log('CLIENT_CREATED', 'client', $client->id, null, $client->toArray());

        return response()->json($client->fresh(), 201);
    }

    public function show(string $id): JsonResponse
    {
        $client = Client::with(['addresses', 'sites'])->findOrFail($id);

        return response()->json($client);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $before = $client->toArray();

        $payload = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'billing_address_id' => ['nullable', 'integer', 'exists:db1.client_addresses,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $client->fill($payload);
        $client->save();

        AuditLogger::log('CLIENT_UPDATED', 'client', $client->id, $before, $client->fresh()->toArray());

        return response()->json($client->fresh());
    }

    public function destroy(string $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $before = $client->toArray();

        $client->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivated_by' => auth()->id(),
        ]);

        AuditLogger::log('CLIENT_DEACTIVATED', 'client', $client->id, $before, $client->fresh()->toArray());

        return response()->json(['message' => 'Client deactivated.']);
    }

    public function sites(string $id): JsonResponse
    {
        $client = Client::findOrFail($id);

        return response()->json(
            Site::with('address')->where('client_id', $client->id)->paginate(15)
        );
    }

    public function storeSite(Request $request, string $id): JsonResponse
    {
        $client = Client::findOrFail($id);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'array'],
            'address.type' => ['required', 'in:HEADQUARTERS,BILLING,OPERATIONAL'],
            'address.address_line1' => ['required', 'string', 'max:255'],
            'address.address_line2' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:100'],
            'address.postal_code' => ['required', 'string', 'max:20'],
            'address.country' => ['nullable', 'string', 'size:2'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $site = DB::connection('db1')->transaction(function () use ($payload, $client) {
            $address = ClientAddress::create([
                'client_id' => $client->id,
                'type' => $payload['address']['type'],
                'address_line1' => $payload['address']['address_line1'],
                'address_line2' => $payload['address']['address_line2'] ?? null,
                'city' => $payload['address']['city'],
                'postal_code' => $payload['address']['postal_code'],
                'country' => $payload['address']['country'] ?? 'PT',
                'is_primary' => false,
                'created_at' => now(),
            ]);

            return Site::create([
                'client_id' => $client->id,
                'name' => $payload['name'],
                'address_id' => $address->id,
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'created_at' => now(),
            ]);
        });

        AuditLogger::log('SITE_CREATED', 'site', $site->id, null, $site->toArray(), $client->id);

        return response()->json($site->load('address'), 201);
    }
}
