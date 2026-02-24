<?php

namespace App\Models\DB1;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $connection = 'db1';

    protected $table = 'roles';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'type',
        'permissions',
        'is_system_role',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system_role' => 'boolean',
    ];
}
