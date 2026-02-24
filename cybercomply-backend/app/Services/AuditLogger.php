<?php

namespace App\Services;

use App\Models\DB1\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogger
{
    public static function log(
        string $action,
        string $entityType,
        string|int $entityId,
        ?array $before = null,
        ?array $after = null,
        ?string $clientId = null
    ): void {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        AuditLog::create([
            'log_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'client_id' => $clientId ?? $user->client_id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'ip_address' => request()->ip() ?? '0.0.0.0',
            'user_agent' => request()->userAgent(),
            'before_state' => $before,
            'after_state' => $after,
            'changes_summary' => self::computeDiff($before, $after),
            'session_id' => request()->hasSession() ? request()->session()->getId() : null,
            'request_id' => request()->header('X-Request-ID') ?? (string) Str::uuid(),
        ]);
    }

    private static function computeDiff(?array $before, ?array $after): ?array
    {
        if (!$before || !$after) {
            return null;
        }

        $diff = [];
        foreach ($after as $key => $value) {
            if (!array_key_exists($key, $before) || $before[$key] !== $value) {
                $diff[$key] = ['from' => $before[$key] ?? null, 'to' => $value];
            }
        }

        return $diff;
    }
}
