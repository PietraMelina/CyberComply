<?php

namespace App\Models\DB1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $connection = 'db1';

    protected $table = 'clients';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'name',
        'vat_number',
        'representative_nif',
        'representative_name',
        'cmd_validated_at',
        'billing_address_id',
        'is_active',
        'deactivated_at',
        'deactivated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cmd_validated_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(ClientAddress::class, 'client_id', 'id');
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'client_id', 'id');
    }
}
