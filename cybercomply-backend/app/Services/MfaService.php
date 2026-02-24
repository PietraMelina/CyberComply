<?php

namespace App\Services;

use App\Mail\MfaTokenMail;
use App\Models\DB1\Token;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class MfaService
{
    public function __construct(private readonly TotpService $totpService)
    {
    }

    public function generateSetup(User $user): array
    {
        $secret = $this->totpService->generateSecret();
        $otpauthUri = $this->totpService->makeOtpAuthUri(
            (string) config('app.name', 'CyberComply'),
            (string) $user->email,
            $secret
        );

        $backupCodesPlain = $this->generateBackupCodes();
        $backupCodesHashed = array_map(
            fn (string $code): string => hash('sha256', strtoupper($code)),
            $backupCodesPlain
        );

        return [
            'secret' => $secret,
            'otpauth_uri' => $otpauthUri,
            'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data='.rawurlencode($otpauthUri),
            'backup_codes_plain' => $backupCodesPlain,
            'backup_codes_hashed' => $backupCodesHashed,
        ];
    }

    public function verifyTotp(User $user, string $code): bool
    {
        if (!$user->mfa_secret) {
            return false;
        }

        return $this->totpService->verify((string) $user->mfa_secret, $code);
    }

    public function verifyAndBurnBackupCode(User $user, string $code): bool
    {
        $codes = $user->mfa_backup_codes ?? [];
        if (!is_array($codes) || empty($codes)) {
            return false;
        }

        $code = strtoupper(trim($code));
        $newCodes = [];
        $matched = false;

        foreach ($codes as $hash) {
            if (!$matched && $this->matchesBackupHash((string) $hash, $code)) {
                $matched = true;
                continue;
            }
            $newCodes[] = $hash;
        }

        if ($matched) {
            $user->update(['mfa_backup_codes' => array_values($newCodes)]);
        }

        return $matched;
    }

    public function sendEmailToken(User $user): void
    {
        Token::query()
            ->where('user_id', $user->id)
            ->where('type', 'MFA')
            ->whereNull('used_at')
            ->whereNull('cancelled_at')
            ->update(['cancelled_at' => now()]);

        $plainToken = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Token::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainToken),
            'type' => 'MFA',
            'payload' => ['channel' => 'email'],
            'expires_at' => now()->addMinutes(10),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        Mail::to($user->email)->send(new MfaTokenMail($plainToken, now()->addMinutes(10)));
    }

    public function verifyEmailToken(User $user, string $code): bool
    {
        $token = Token::query()
            ->where('user_id', $user->id)
            ->where('type', 'MFA')
            ->whereNull('used_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (!$token) {
            return false;
        }

        if (!hash_equals((string) $token->token, hash('sha256', trim($code)))) {
            return false;
        }

        $token->used_at = now();
        $token->save();

        return true;
    }

    public function enable(User $user, string $secret, array $backupCodesHashed): void
    {
        $user->update([
            'mfa_secret' => $secret,
            'mfa_enabled' => true,
            'mfa_backup_codes' => array_values($backupCodesHashed),
        ]);
    }

    public function disable(User $user): void
    {
        $user->update([
            'mfa_secret' => null,
            'mfa_enabled' => false,
            'mfa_backup_codes' => null,
        ]);

        Token::query()
            ->where('user_id', $user->id)
            ->where('type', 'MFA')
            ->whereNull('used_at')
            ->whereNull('cancelled_at')
            ->update(['cancelled_at' => now()]);
    }

    public function backupCodesRemaining(User $user): int
    {
        $codes = $user->mfa_backup_codes ?? [];
        if (!is_array($codes)) {
            return 0;
        }

        return count($codes);
    }

    private function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        while (count($codes) < $count) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }

        return $codes;
    }

    private function matchesBackupHash(string $hash, string $code): bool
    {
        if (strlen($hash) === 64) {
            return hash_equals($hash, hash('sha256', $code));
        }

        return password_verify($code, $hash);
    }
}

