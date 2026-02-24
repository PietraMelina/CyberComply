<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MfaService;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Facades\JWTAuth;

class MfaController extends Controller
{
    public function __construct(
        private readonly MfaService $mfaService,
        private readonly TotpService $totpService
    ) {
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'enabled' => (bool) $user->mfa_enabled,
            'backup_codes_remaining' => $this->mfaService->backupCodesRemaining($user),
        ]);
    }

    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->mfa_enabled) {
            return response()->json(['error' => '2FA já ativado'], 400);
        }

        $setup = $this->mfaService->generateSetup($user);

        Cache::put("mfa_setup_{$user->id}", [
            'secret' => $setup['secret'],
            'backup_codes_hashed' => $setup['backup_codes_hashed'],
            'backup_codes_plain' => $setup['backup_codes_plain'],
        ], now()->addMinutes(10));

        AuditLogger::log('MFA_SETUP_INITIATED', 'user', $user->id, null, null, $user->client_id);

        return response()->json([
            'qr_code_url' => $setup['qr_code_url'],
            'otpauth_uri' => $setup['otpauth_uri'],
            'backup_codes' => $setup['backup_codes_plain'],
            'message' => 'Escaneie o QR e confirme com o primeiro código.',
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();

        $setup = Cache::get("mfa_setup_{$user->id}");
        if (!is_array($setup) || empty($setup['secret'])) {
            return response()->json(['error' => 'Setup expirado. Reinicie.'], 400);
        }

        if (!$this->totpService->verify((string) $setup['secret'], (string) $request->input('code'))) {
            AuditLogger::log('MFA_SETUP_FAILED', 'user', $user->id, null, ['reason' => 'invalid_code'], $user->client_id);
            return response()->json(['error' => 'Código inválido.'], 400);
        }

        $this->mfaService->enable($user, (string) $setup['secret'], (array) ($setup['backup_codes_hashed'] ?? []));
        Cache::forget("mfa_setup_{$user->id}");

        AuditLogger::log('MFA_ENABLED', 'user', $user->id, null, null, $user->client_id);

        return response()->json([
            'message' => '2FA ativado com sucesso.',
            'backup_codes_remaining' => $this->mfaService->backupCodesRemaining($user->fresh()),
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();

        if (!$user->mfa_enabled) {
            return response()->json(['error' => '2FA não está ativado.'], 400);
        }

        if (!$this->mfaService->verifyTotp($user, (string) $request->input('code'))) {
            return response()->json(['error' => 'Código TOTP inválido.'], 401);
        }

        $this->mfaService->disable($user);
        AuditLogger::log('MFA_DISABLED', 'user', $user->id, null, null, $user->client_id);

        return response()->json(['message' => '2FA desativado com sucesso.']);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'temp_token' => ['required', 'string'],
            'code' => ['required', 'string'],
            'type' => ['required', 'in:totp,backup'],
        ]);

        $user = $this->resolveTempTokenUser((string) $request->input('temp_token'));
        if (!$user || !$user->mfa_enabled) {
            return response()->json(['error' => 'Token inválido ou MFA não ativado.'], 401);
        }

        $code = (string) $request->input('code');
        $type = (string) $request->input('type');
        $valid = $type === 'backup'
            ? $this->mfaService->verifyAndBurnBackupCode($user, $code)
            : $this->mfaService->verifyTotp($user, $code);

        if (!$valid) {
            AuditLogger::log('MFA_VERIFICATION_FAILED', 'user', $user->id, null, ['type' => $type], $user->client_id);
            return response()->json(['error' => 'Código inválido.'], 401);
        }

        AuditLogger::log('MFA_VERIFICATION_SUCCESS', 'user', $user->id, null, ['type' => $type], $user->client_id);
        $token = auth('api')->login($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    public function email(Request $request): JsonResponse
    {
        $request->validate(['temp_token' => ['required', 'string']]);

        $user = $this->resolveTempTokenUser((string) $request->input('temp_token'));
        if (!$user || !$user->mfa_enabled) {
            return response()->json(['error' => 'Token inválido ou MFA não ativado.'], 401);
        }

        $this->mfaService->sendEmailToken($user);
        AuditLogger::log('MFA_EMAIL_TOKEN_SENT', 'user', $user->id, null, null, $user->client_id);

        return response()->json([
            'message' => 'Código enviado para o email.',
            'expires_in' => 600,
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'temp_token' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = $this->resolveTempTokenUser((string) $request->input('temp_token'));
        if (!$user || !$user->mfa_enabled) {
            return response()->json(['error' => 'Token inválido ou MFA não ativado.'], 401);
        }

        if (!$this->mfaService->verifyEmailToken($user, (string) $request->input('code'))) {
            AuditLogger::log('MFA_EMAIL_VERIFICATION_FAILED', 'user', $user->id, null, null, $user->client_id);
            return response()->json(['error' => 'Código inválido ou expirado.'], 401);
        }

        AuditLogger::log('MFA_EMAIL_VERIFIED', 'user', $user->id, null, null, $user->client_id);
        $token = auth('api')->login($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    private function resolveTempTokenUser(string $token): ?User
    {
        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            if ((string) $payload->get('type') !== 'mfa_temp') {
                return null;
            }

            return User::query()->find((string) $payload->get('sub'));
        } catch (\Throwable) {
            return null;
        }
    }
}

