<?php

namespace App\Models\DB1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Site extends Model
{
    protected $connection = 'db1';

    protected $table = 'sites';

    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'name',
        'address_id',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(ClientAddress::class, 'address_id', 'id');
    }
}
