<?php

namespace App\Models\DBAssets;

use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    protected $connection = 'db_assets';

    protected $table = 'media_assets';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'asset_type',
        'file_name',
        'mime_type',
        'file_size_bytes',
        'storage_path',
        'checksum_sha256',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

