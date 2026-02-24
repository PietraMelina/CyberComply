<?php

namespace App\Models\DB2;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $connection = 'db2';
    protected $table = 'modules';
    public $timestamps = false;

    protected $fillable = [
        'client_id', 'code', 'name', 'description', 'version', 'is_active', 'created_by', 'created_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'module_id', 'id')->orderBy('order_index');
    }
}
