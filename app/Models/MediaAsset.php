<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MediaAsset extends Model
{
    protected $fillable = [
        'asset_type',
        'storage_type',
        'file_path',
        'external_url',
        'thumbnail_path',
        'caption',
        'credit',
    ];

    public function contentNodes(): BelongsToMany
    {
        return $this->belongsToMany(ContentNode::class, 'content_node_media')
            ->withPivot(['sort_order'])
            ->withTimestamps();
    }
}
