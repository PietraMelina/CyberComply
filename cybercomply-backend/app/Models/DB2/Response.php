<?php

namespace App\Models\DB2;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Response extends Model
{
    protected $connection = 'db2';
    protected $table = 'responses';
    public $timestamps = false;

    protected $fillable = [
        'client_id', 'question_id', 'site_id', 'version', 'status', 'comment', 'answered_by', 'answered_at',
        'previous_version_id', 'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'answered_at' => 'datetime',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id', 'id');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id', 'id');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(Evidence::class, 'response_id', 'id');
    }
}
