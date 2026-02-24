<?php

namespace App\Models\DB1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientAddress extends Model
{
    protected $connection = 'db1';

    protected $table = 'client_addresses';

    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'type',
        'address_line1',
        'address_line2',
        'city',
        'postal_code',
        'country',
        'is_primary',
        'created_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }
}
