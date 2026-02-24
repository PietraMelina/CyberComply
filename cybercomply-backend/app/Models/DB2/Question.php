<?php

namespace App\Models\DB2;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $connection = 'db2';
    protected $table = 'questions';
    public $timestamps = false;

    protected $fillable = [
        'module_id', 'client_id', 'question_text', 'weight', 'order_index', 'is_active', 'created_at',
    ];

    protected $casts = [
        'weight' => 'float',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id', 'id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class, 'question_id', 'id');
    }
}
