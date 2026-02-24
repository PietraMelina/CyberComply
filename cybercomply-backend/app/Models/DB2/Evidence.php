<?php

namespace App\Models\DB2;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evidence extends Model
{
    protected $connection = 'db2';
    protected $table = 'evidences';
    public $timestamps = false;

    protected $fillable = [
        'client_id', 'response_id', 'internal_token', 'original_filename', 'file_size_bytes', 'mime_type',
        'storage_path', 'checksum_sha256', 'uploaded_by', 'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(Response::class, 'response_id', 'id');
    }
}
