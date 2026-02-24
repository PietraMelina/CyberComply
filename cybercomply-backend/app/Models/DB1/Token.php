<?php

namespace App\Models\DB1;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $connection = 'db1';

    protected $table = 'tokens';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token',
        'type',
        'payload',
        'expires_at',
        'used_at',
        'cancelled_at',
        'cancelled_by',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
