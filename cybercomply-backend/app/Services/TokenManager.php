<?php

namespace App\Services;

use App\Models\DB1\Token;
use Carbon\Carbon;

class TokenManager
{
    public function generate(
        string $userId,
        string $type,
        ?array $payload = null,
        int $expiresMinutes = 10
    ): array {
        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);
        $expiresAt = Carbon::now()->addMinutes(min($expiresMinutes, 10));

        Token::create([
            'user_id' => $userId,
            'token' => $hashedToken,
            'type' => $type,
            'payload' => $payload,
            'expires_at' => $expiresAt,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        return [
            'plain_token' => $plainToken,
            'expires_at' => $expiresAt,
        ];
    }

    public function consume(string $plainToken, string $type): ?Token
    {
        $hashedToken = hash('sha256', $plainToken);

        $token = Token::query()
            ->where('token', $hashedToken)
            ->where('type', $type)
            ->whereNull('used_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>=', now())
            ->first();

        if (!$token) {
            return null;
        }

        $token->used_at = now();
        $token->save();

        return $token;
    }
}

