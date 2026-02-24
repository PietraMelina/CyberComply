<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\DB1\Token;
use App\Providers\RouteServiceProvider;
use App\Services\TotpService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly TotpService $totpService)
    {
    }

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => false,
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $user = $request->user()->load('role');
        $roleName = (string) ($user->role?->name ?? '');

        if ($roleName !== 'MASTER' && $user->mfa_enabled) {
            if (!$user->mfa_secret) {
                Auth::guard('web')->logout();
                throw ValidationException::withMessages([
                    'mfa_code' => 'MFA da conta está inconsistente. Contacte suporte.',
                ]);
            }

            $emailToken = trim((string) $request->input('email_token', ''));
            $code = (string) $request->input('mfa_code', '');
            $validTotp = $code !== '' && $this->totpService->verify((string) $user->mfa_secret, $code);
            $validBackup = !$validTotp && $code !== '' && $this->consumeBackupCode($user, $code);
            $validEmailToken = !$validTotp && !$validBackup && $emailToken !== '' && $this->consumeEmailToken($user->id, $emailToken);

            if ($code === '' && $emailToken === '') {
                $this->sendEmailMfaToken($user->id, $user->email, $user->client_id);
                Auth::guard('web')->logout();
                throw ValidationException::withMessages([
                    'email_token' => 'Token enviado por email. Preencha "Token por email" ou use o código do app autenticador.',
                ]);
            }

            if (!$validTotp && !$validBackup && !$validEmailToken) {
                Auth::guard('web')->logout();
                throw ValidationException::withMessages([
                    'mfa_code' => 'Código MFA inválido.',
                    'email_token' => 'Token por email inválido ou expirado.',
                ]);
            }
        }

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->forget('api_access_token');

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function sessionFromApiToken(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        try {
            $user = JWTAuth::setToken((string) $payload['access_token'])->authenticate();
        } catch (\Throwable) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        if (!$user) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('api_access_token', (string) $payload['access_token']);

        return response()->json(['message' => 'Session established.']);
    }

    private function consumeBackupCode($user, string $inputCode): bool
    {
        $hashedCodes = $user->mfa_backup_codes ?? [];
        if (!is_array($hashedCodes) || empty($hashedCodes)) {
            return false;
        }

        $inputHash = hash('sha256', strtoupper(trim($inputCode)));
        $newCodes = [];
        $matched = false;

        foreach ($hashedCodes as $hash) {
            if (!$matched && hash_equals((string) $hash, $inputHash)) {
                $matched = true;
                continue;
            }
            $newCodes[] = $hash;
        }

        if ($matched) {
            $user->mfa_backup_codes = $newCodes;
            $user->save();
        }

        return $matched;
    }

    private function sendEmailMfaToken(string $userId, string $email, ?string $clientId): void
    {
        $plain = strtoupper(substr(bin2hex(random_bytes(8)), 0, 8));
        $hashed = hash('sha256', $plain);

        Token::create([
            'user_id' => $userId,
            'token' => $hashed,
            'type' => 'MFA',
            'payload' => ['channel' => 'email'],
            'expires_at' => now()->addMinutes(10),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        Mail::raw("Seu token MFA do CyberComply: {$plain}\nValidade: 10 minutos.", function ($message) use ($email): void {
            $message->to($email)->subject('CyberComply - Token MFA');
        });

        AuditLogger::log('TOKEN_GENERATED', 'token', 'email_mfa', null, ['channel' => 'email'], $clientId);
    }

    private function consumeEmailToken(string $userId, string $plain): bool
    {
        $hashed = hash('sha256', strtoupper(trim($plain)));

        $token = Token::query()
            ->where('user_id', $userId)
            ->where('type', 'MFA')
            ->where('token', $hashed)
            ->whereNull('used_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>=', now())
            ->orderByDesc('id')
            ->first();

        if (!$token) {
            return false;
        }

        $token->used_at = now();
        $token->save();

        return true;
    }
}
