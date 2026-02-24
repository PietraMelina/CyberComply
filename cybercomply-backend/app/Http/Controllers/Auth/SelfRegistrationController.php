<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DB1\Client;
use App\Models\DB1\ClientAddress;
use App\Models\DB1\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ClientIdGenerator;
use App\Services\UserIdGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SelfRegistrationController extends Controller
{
    public function __construct(
        private readonly ClientIdGenerator $clientIdGenerator,
        private readonly UserIdGenerator $userIdGenerator
    ) {
    }

    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_type' => ['required', 'in:PRIV,PUBL'],
            'company_vat' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:db1.users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
            'billing_address.line1' => ['required', 'string', 'max:255'],
            'billing_address.line2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required', 'string', 'max:100'],
            'billing_address.postal_code' => ['required', 'string', 'max:20'],
            'billing_address.country' => ['nullable', 'string', 'size:2'],
            'accepted_terms' => ['accepted'],
        ]);

        $adminRole = Role::query()->where('name', 'ADMIN_CLIENTE')->firstOrFail();
        $companyCode = $this->companyCodeFromName((string) $payload['company_name']);

        [$client, $user] = DB::connection('db1')->transaction(function () use ($payload, $adminRole, $companyCode): array {
            $client = Client::create([
                'id' => $this->clientIdGenerator->generate((string) $payload['company_type'], (int) date('Y')),
                'type' => (string) $payload['company_type'],
                'name' => (string) $payload['company_name'],
                'vat_number' => $payload['company_vat'] ?? null,
                'is_active' => true,
            ]);

            $address = ClientAddress::create([
                'client_id' => $client->id,
                'type' => 'BILLING',
                'address_line1' => (string) $payload['billing_address']['line1'],
                'address_line2' => $payload['billing_address']['line2'] ?? null,
                'city' => (string) $payload['billing_address']['city'],
                'postal_code' => (string) $payload['billing_address']['postal_code'],
                'country' => strtoupper((string) ($payload['billing_address']['country'] ?? 'PT')),
                'is_primary' => true,
                'created_at' => now(),
            ]);

            $client->billing_address_id = $address->id;
            $client->save();

            $user = User::create([
                'id' => $this->userIdGenerator->generate($companyCode),
                'client_id' => $client->id,
                'display_name' => Str::before((string) $payload['email'], '@'),
                'email' => (string) $payload['email'],
                'password_hash' => Hash::make((string) $payload['password']),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
                'accepted_terms_at' => now(),
                'accepted_terms_version' => 'v1',
            ]);

            return [$client, $user];
        });

        Auth::login($user);
        $request->session()->regenerate();

        AuditLogger::log('CLIENT_CREATED', 'client', $client->id, null, $client->toArray(), $client->id);
        AuditLogger::log('USER_CREATED', 'user', $user->id, null, $user->toArray(), $client->id);

        return redirect()->route('security.mfa.show')
            ->with('status', 'Conta criada. Configure o MFA para concluir o primeiro acesso.');
    }

    private function companyCodeFromName(string $name): string
    {
        $code = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', Str::ascii($name)), 0, 4));

        return str_pad($code, 4, 'X');
    }
}
