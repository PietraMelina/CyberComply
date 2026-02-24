<?php

namespace App\Http\Controllers;

use App\Services\MfaService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        private readonly MfaService $mfaService
    )
    {
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'mfa_code' => ['nullable', 'string'],
            'email_token' => ['nullable', 'string'],
        ]);

        $token = auth('api')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'is_active' => 1,
        ]);

        if (!$token) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user = auth('api')->user();
        $user->loadMissing('role');
        $roleName = (string) ($user->role?->name ?? '');

        if ($roleName !== 'MASTER' && $user->mfa_enabled) {
            $code = (string) ($credentials['mfa_code'] ?? '');
            $emailToken = (string) ($credentials['email_token'] ?? '');
            $validTotp = $code !== '' && $this->mfaService->verifyTotp($user, $code);
            $validBackup = $code !== '' && !$validTotp && $this->mfaService->verifyAndBurnBackupCode($user, $code);
            $validEmail = $emailToken !== '' && !$validTotp && !$validBackup && $this->mfaService->verifyEmailToken($user, $emailToken);

            if (!$validTotp && !$validBackup && !$validEmail) {
                AuditLogger::log('MFA_REQUIRED', 'user', $user->id, null, ['methods' => ['totp', 'backup', 'email']], $user->client_id);
                auth('api')->logout();

                $tempToken = JWTAuth::claims(['type' => 'mfa_temp'])->fromUser($user);

                return response()->json([
                    'mfa_required' => true,
                    'temp_token' => $tempToken,
                    'methods' => ['totp', 'backup', 'email'],
                ], 200);
            }
        }

        $user->last_login_at = now();
        $user->save();

        AuditLogger::log('USER_LOGIN_SUCCESS', 'user', $user->id);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user->load('role'),
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json(auth('api')->user()?->load('role'));
    }

    public function logout(): JsonResponse
    {
        $user = auth('api')->user();
        if ($user) {
            AuditLogger::log('USER_LOGOUT', 'user', $user->id);
        }

        auth('api')->logout();

        return response()->json(['message' => 'Logged out.']);
    }

    public function refresh(): JsonResponse
    {
        return response()->json([
            'access_token' => JWTAuth::parseToken()->refresh(),
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
