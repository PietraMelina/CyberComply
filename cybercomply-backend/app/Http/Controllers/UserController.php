<?php

namespace App\Http\Controllers;

use App\Models\DB1\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TotpService;
use App\Services\UserIdGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct(
        private readonly UserIdGenerator $userIdGenerator,
        private readonly TotpService $totpService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $authUser = $request->user();

        $query = User::with('role');

        if ($authUser->client_id) {
            $query->where('client_id', $authUser->client_id);
        } elseif ($request->filled('client_id')) {
            $query->where('client_id', $request->string('client_id'));
        }

        return response()->json($query->paginate((int) $request->integer('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $authUser = $request->user();

        $payload = $request->validate([
            'display_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('db1.users', 'email')],
            'password' => ['required', 'string', Password::min(10)->mixedCase()->numbers()->symbols()],
            'role_id' => ['required', 'integer', 'exists:db1.roles,id'],
            'client_id' => ['nullable', 'string', 'size:14', 'exists:db1.clients,id'],
            'accepted_terms_version' => ['required', 'string', 'max:10'],
            'company_code' => ['nullable', 'string', 'size:4'],
        ]);

        $role = Role::findOrFail($payload['role_id']);

        if ($authUser->client_id) {
            if ($role->type !== 'CLIENT') {
                abort(403, 'Tenant users cannot assign internal roles.');
            }

            $payload['client_id'] = $authUser->client_id;
        }

        if (empty($payload['client_id']) && $role->type === 'CLIENT') {
            abort(422, 'Client role requires client_id.');
        }

        if (!empty($payload['client_id']) && $role->type === 'INTERNAL') {
            abort(422, 'Internal role cannot have client_id.');
        }

        $user = User::create([
            'id' => $this->userIdGenerator->generate($payload['company_code'] ?? 'USER'),
            'client_id' => $payload['client_id'] ?? null,
            'display_name' => $payload['display_name'] ?? Str::before((string) $payload['email'], '@'),
            'email' => $payload['email'],
            'password_hash' => Hash::make($payload['password']),
            'role_id' => $role->id,
            'is_active' => true,
            'accepted_terms_at' => now(),
            'accepted_terms_version' => $payload['accepted_terms_version'],
        ]);

        AuditLogger::log('USER_CREATED', 'user', $user->id, null, $user->toArray(), $user->client_id);

        return response()->json($user->load('role'), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $authUser = $request->user();

        $user = User::with('role')->findOrFail($id);

        if ($authUser->client_id && $user->client_id !== $authUser->client_id) {
            abort(403, 'Tenant isolation violation.');
        }

        return response()->json($user);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $authUser = $request->user();
        $user = User::findOrFail($id);

        if ($authUser->client_id && $user->client_id !== $authUser->client_id) {
            abort(403, 'Tenant isolation violation.');
        }

        $before = $user->toArray();

        $payload = $request->validate([
            'display_name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email', Rule::unique('db1.users', 'email')->ignore($user->id, 'id')],
            'password' => ['nullable', 'string', Password::min(10)->mixedCase()->numbers()->symbols()],
            'is_active' => ['sometimes', 'boolean'],
            'accepted_terms_version' => ['sometimes', 'string', 'max:10'],
        ]);

        if (array_key_exists('password', $payload) && !empty($payload['password'])) {
            $payload['password_hash'] = Hash::make($payload['password']);
            unset($payload['password']);
        } else {
            unset($payload['password']);
        }

        $user->fill($payload);
        $user->save();

        AuditLogger::log('USER_UPDATED', 'user', $user->id, $before, $user->fresh()->toArray(), $user->client_id);

        return response()->json($user->fresh()->load('role'));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $authUser = $request->user();
        $user = User::findOrFail($id);

        if ($authUser->client_id && $user->client_id !== $authUser->client_id) {
            abort(403, 'Tenant isolation violation.');
        }

        $before = $user->toArray();

        $user->update([
            'is_active' => false,
            'email' => sprintf('%s.inactive.%d', $user->email, time()),
        ]);

        AuditLogger::log('USER_DEACTIVATED', 'user', $user->id, $before, $user->fresh()->toArray(), $user->client_id);

        return response()->json(['message' => 'User deactivated.']);
    }

    public function changeRole(Request $request, string $id): JsonResponse
    {
        $authUser = $request->user();
        $user = User::findOrFail($id);

        if ($authUser->client_id && $user->client_id !== $authUser->client_id) {
            abort(403, 'Tenant isolation violation.');
        }

        $payload = $request->validate([
            'role_id' => ['required', 'integer', 'exists:db1.roles,id'],
        ]);

        $role = Role::findOrFail($payload['role_id']);
        if ($authUser->client_id && $role->type !== 'CLIENT') {
            abort(403, 'Tenant users cannot assign internal roles.');
        }

        $before = $user->toArray();
        $user->update(['role_id' => $role->id]);

        AuditLogger::log('ROLE_CHANGED', 'user', $user->id, $before, $user->fresh()->toArray(), $user->client_id);

        return response()->json($user->fresh()->load('role'));
    }

    public function enableMfa(Request $request, string $id): JsonResponse
    {
        $authUser = $request->user();
        $user = User::findOrFail($id);

        if ($authUser->client_id && $user->client_id !== $authUser->client_id) {
            abort(403, 'Tenant isolation violation.');
        }

        $secret = $this->totpService->generateSecret();
        $backupCodes = $this->generateBackupCodes();
        $before = $user->toArray();

        $user->update([
            'mfa_secret' => $secret,
            'mfa_enabled' => true,
            'mfa_backup_codes' => array_map(fn (string $code): string => hash('sha256', $code), $backupCodes),
        ]);

        $issuer = (string) config('app.name', 'CyberComply');
        $otpauthUri = $this->totpService->makeOtpAuthUri($issuer, $user->email, $secret);
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data='.rawurlencode($otpauthUri);

        AuditLogger::log('MFA_ENABLED', 'user', $user->id, $before, $user->fresh()->toArray(), $user->client_id);

        return response()->json([
            'user_id' => $user->id,
            'mfa_enabled' => true,
            'secret' => $secret,
            'backup_codes' => $backupCodes,
            'otpauth_uri' => $otpauthUri,
            'qr_image_url' => $qrImageUrl,
        ]);
    }

    public function disableMfa(Request $request, string $id): JsonResponse
    {
        $authUser = $request->user();
        $user = User::findOrFail($id);

        if ($authUser->client_id && $user->client_id !== $authUser->client_id) {
            abort(403, 'Tenant isolation violation.');
        }

        $payload = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if (!$user->mfa_enabled || !$user->mfa_secret) {
            return response()->json(['message' => 'MFA is not enabled.'], 422);
        }

        if (!$this->totpService->verify($user->mfa_secret, $payload['code'])) {
            return response()->json(['message' => 'Invalid MFA code.'], 422);
        }

        $before = $user->toArray();

        $user->update([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_backup_codes' => null,
        ]);

        AuditLogger::log('MFA_DISABLED', 'user', $user->id, $before, $user->fresh()->toArray(), $user->client_id);

        return response()->json(['message' => 'MFA disabled.']);
    }

    private function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        while (count($codes) < $count) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }

        return $codes;
    }
}
