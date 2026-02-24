<?php

namespace App\Models\DB2;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $connection = 'db2';
    protected $table = 'audit_logs';
    public $timestamps = false;

    protected $fillable = [
        'log_id', 'user_id', 'client_id', 'action', 'entity_type', 'entity_id', 'ip_address', 'user_agent',
        'before_state', 'after_state', 'changes_summary', 'session_id', 'request_id', 'created_at',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'changes_summary' => 'array',
        'created_at' => 'datetime',
    ];
}
