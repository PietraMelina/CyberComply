<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DB1\Client;
use App\Models\DB1\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Cmd\CmdServiceInterface;
use App\Services\ClientIdGenerator;
use App\Services\TokenManager;
use App\Services\UserIdGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CmdAuthController extends Controller
{
    public function __construct(
        private readonly CmdServiceInterface $cmdService,
        private readonly ClientIdGenerator $clientIdGenerator,
        private readonly UserIdGenerator $userIdGenerator,
        private readonly TokenManager $tokenManager
    ) {
    }

    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('cmd_state', $state);

        return redirect($this->cmdService->getAuthorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = (string) $request->input('state');
        if ($state === '' || $state !== $request->session()->pull('cmd_state')) {
            abort(403, 'Invalid CMD state.');
        }

        $identity = $this->resolveIdentity($request);
        $request->session()->put('cmd_identity', $identity);

        $existingUser = User::query()->where('nif', $identity['nif'])->first();
        if ($existingUser) {
            if ($existingUser->is_active) {
                Auth::login($existingUser);
                $request->session()->regenerate();
                AuditLogger::log(
                    'CMD_LOGIN_SUCCESS',
                    'user',
                    $existingUser->id,
                    null,
                    ['nif_masked' => $this->maskNif($identity['nif'])],
                    $existingUser->client_id
                );

                return redirect()->route('dashboard');
            }

            $request->session()->put('cmd_pending_verification', [
                'user_id' => $existingUser->id,
                'client_id' => $existingUser->client_id,
                'email_masked' => $this->maskEmail($existingUser->email),
            ]);

            return redirect()->route('register.pending');
        }

        return redirect()->route('register.new');
    }

    public function showCompleteRegistration(Request $request): Response
    {
        $identity = $request->session()->get('cmd_identity');
        if (!$identity) {
            abort(403, 'CMD validation is required.');
        }

        return Inertia::render('Auth/CompleteRegistration', [
            'nif' => $identity['nif'],
            'nome' => $identity['name'],
            'cmd_validated' => true,
        ]);
    }

    public function completeRegistration(Request $request): RedirectResponse
    {
        $identity = $request->session()->get('cmd_identity');
        if (!$identity) {
            abort(403, 'CMD validation is required.');
        }

        $payload = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_type' => ['required', 'in:PRIV,PUBL'],
            'company_vat' => ['nullable', 'string', 'max:50'],
            'email_corporate' => ['required', 'email', 'unique:db1.users,email'],
            'billing_address' => ['required', 'array'],
            'billing_address.line1' => ['required', 'string', 'max:255'],
            'billing_address.line2' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required', 'string', 'max:100'],
            'billing_address.postal_code' => ['required', 'string', 'max:20'],
            'billing_address.country' => ['nullable', 'string', 'size:2'],
        ]);

        $nif = (string) $identity['nif'];
        $name = (string) $identity['name'];

        if (Client::query()->where('representative_nif', $nif)->exists()) {
            return redirect()->route('login')->with('status', 'NIF já registado. Faça login com CMD.');
        }

        if (User::query()->where('nif', $nif)->exists()) {
            return redirect()->route('register.pending')->with('status', 'Registo já iniciado. Verifique o email corporativo.');
        }

        $adminRole = Role::query()->where('name', 'ADMIN_CLIENTE')->firstOrFail();
        $companyCode = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', Str::ascii($payload['company_name'])), 0, 4));
        $companyCode = str_pad($companyCode, 4, 'X');

        [$client, $user, $plainToken] = DB::connection('db1')->transaction(function () use ($payload, $nif, $name, $adminRole, $companyCode) {
            $client = Client::create([
                'id' => $this->clientIdGenerator->generate($payload['company_type'], (int) date('Y')),
                'type' => $payload['company_type'],
                'name' => $payload['company_name'],
                'vat_number' => $payload['company_vat'] ?? null,
                'representative_nif' => $nif,
                'representative_name' => $name,
                'cmd_validated_at' => now(),
                'is_active' => false,
            ]);

            $address = $client->addresses()->create([
                'type' => 'BILLING',
                'address_line1' => $payload['billing_address']['line1'],
                'address_line2' => $payload['billing_address']['line2'] ?? null,
                'city' => $payload['billing_address']['city'],
                'postal_code' => $payload['billing_address']['postal_code'],
                'country' => $payload['billing_address']['country'] ?? 'PT',
                'is_primary' => true,
                'created_at' => now(),
            ]);

            $client->billing_address_id = $address->id;
            $client->save();

            $user = User::create([
                'id' => $this->userIdGenerator->generate($companyCode),
                'client_id' => $client->id,
                'nif' => $nif,
                'email' => $payload['email_corporate'],
                'password_hash' => password_hash(Str::random(32), PASSWORD_BCRYPT),
                'role_id' => $adminRole->id,
                'is_active' => false,
                'accepted_terms_at' => now(),
                'accepted_terms_version' => 'v1',
            ]);

            $tokenData = $this->tokenManager->generate(
                userId: $user->id,
                type: 'EMAIL_VERIFY',
                payload: ['client_id' => $client->id],
                expiresMinutes: 10
            );

            return [$client, $user, $tokenData['plain_token']];
        });

        $this->sendVerificationEmail($user->email, $plainToken);

        $request->session()->put('cmd_pending_verification', [
            'user_id' => $user->id,
            'client_id' => $client->id,
            'email_masked' => $this->maskEmail($user->email),
        ]);

        Auth::login($user);
        AuditLogger::log(
            'CLIENT_REGISTRATION_CMD',
            'client',
            $client->id,
            null,
            ['nif_masked' => $this->maskNif($nif), 'email' => $this->maskEmail($user->email)],
            $client->id
        );
        Auth::logout();

        return redirect()->route('register.pending');
    }

    public function pending(Request $request): Response
    {
        $pending = $request->session()->get('cmd_pending_verification');
        if (!$pending) {
            abort(404);
        }

        return Inertia::render('Auth/PendingVerification', [
            'email' => $pending['email_masked'],
        ]);
    }

    public function verifyEmail(Request $request, string $token): RedirectResponse
    {
        $tokenRow = $this->tokenManager->consume($token, 'EMAIL_VERIFY');
        if (!$tokenRow) {
            return redirect()->route('login')->with('status', 'Token inválido ou expirado.');
        }

        $user = User::query()->findOrFail($tokenRow->user_id);
        $client = Client::query()->findOrFail($user->client_id);

        $beforeUser = $user->toArray();
        $beforeClient = $client->toArray();

        DB::connection('db1')->transaction(function () use ($user, $client): void {
            $user->is_active = true;
            $user->email_verified_at = now();
            $user->save();

            $client->is_active = true;
            $client->save();
        });

        Auth::login($user);
        AuditLogger::log('USER_EMAIL_VERIFIED', 'user', $user->id, $beforeUser, $user->fresh()->toArray(), $user->client_id);
        AuditLogger::log('CLIENT_ACTIVATED', 'client', $client->id, $beforeClient, $client->fresh()->toArray(), $client->id);

        $request->session()->forget('cmd_pending_verification');
        $request->session()->forget('cmd_identity');

        return redirect()->route('dashboard');
    }

    public function resendToken(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('cmd_pending_verification');
        if (!$pending) {
            return redirect()->route('login')->with('status', 'Sessão CMD expirada. Entre novamente.');
        }

        $user = User::query()->findOrFail($pending['user_id']);
        if ($user->is_active) {
            return redirect()->route('dashboard');
        }

        $tokenData = $this->tokenManager->generate(
            userId: $user->id,
            type: 'EMAIL_VERIFY',
            payload: ['client_id' => $user->client_id],
            expiresMinutes: 10
        );

        $this->sendVerificationEmail($user->email, $tokenData['plain_token']);

        return back()->with('status', 'Novo email de verificação enviado.');
    }

    private function resolveIdentity(Request $request): array
    {
        return $this->cmdService->resolveIdentity($request);
    }

    private function sendVerificationEmail(string $to, string $plainToken): void
    {
        $verificationUrl = route('verify.email', ['token' => $plainToken]);

        if (app()->environment('local') && (bool) config('services.cmd.mock_enabled', true)) {
            Log::info('CMD mock verification URL', [
                'email' => $this->maskEmail($to),
                'verification_url' => $verificationUrl,
            ]);
        }

        try {
            Mail::raw(
                "Confirme o seu email corporativo no CyberComply: {$verificationUrl}\nEste link expira em 10 minutos.",
                fn ($message) => $message->to($to)->subject('CyberComply - Verificação de Email')
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to send CMD verification email', [
                'email' => $this->maskEmail($to),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function maskNif(string $nif): string
    {
        return substr($nif, 0, 3).'*****'.substr($nif, -1);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);

        return substr($local, 0, 2).'***@'.$domain;
    }
}
