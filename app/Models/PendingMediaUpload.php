<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingMediaUpload extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'asset_type',
        'storage_disk',
        'temp_path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'size_bytes' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
