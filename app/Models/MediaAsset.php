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

    public function publicUrl(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        if (str_starts_with($this->file_path, 'demo/')) {
            return asset($this->file_path);
        }

        return asset('storage/'.$this->file_path);
    }
}
