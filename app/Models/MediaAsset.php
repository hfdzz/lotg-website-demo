<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class MediaAsset extends Model
{
    protected $fillable = [
        'asset_type',
        'storage_type',
        'is_library_item',
        'file_path',
        'external_url',
        'thumbnail_path',
        'caption',
        'credit',
    ];

    protected $casts = [
        'id' => 'integer',
        'is_library_item' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $mediaAsset): void {
            if (! $mediaAsset->isDirty('is_library_item') && in_array($mediaAsset->asset_type, ['image', 'video'], true)) {
                $mediaAsset->is_library_item = true;
            }
        });
    }

    public function contentNodes(): BelongsToMany
    {
        return $this->belongsToMany(ContentNode::class, 'content_node_media')
            ->withPivot(['sort_order'])
            ->withTimestamps();
    }

    public function scopeLibraryItems(Builder $query): Builder
    {
        return $query->where('is_library_item', true);
    }

    public function scopeOfAssetType(Builder $query, string $assetType): Builder
    {
        return $query->where('asset_type', $assetType);
    }

    public function adminLabel(): string
    {
        return $this->caption
            ?: ($this->storage_type === 'upload' && $this->file_path
                ? basename($this->file_path)
                : ($this->external_url ?: ucfirst($this->asset_type).' #'.$this->id));
    }

    public function adminSource(): ?string
    {
        return $this->storage_type === 'upload' ? $this->file_path : $this->external_url;
    }

    public function thumbnailUrl(): ?string
    {
        if ($this->thumbnail_path) {
            return $this->resolveAssetUrl($this->thumbnail_path);
        }

        if ($this->asset_type === 'image') {
            return $this->publicUrl();
        }

        if ($this->asset_type === 'video') {
            $youtubeId = $this->youtubeId();

            return $youtubeId ? 'https://i.ytimg.com/vi/'.$youtubeId.'/hqdefault.jpg' : null;
        }

        return null;
    }

    public function publicUrl(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return $this->resolveAssetUrl($this->file_path);
    }

    public function youtubeId(): ?string
    {
        return self::parseYouTubeId($this->external_url);
    }

    public function youtubeEmbedUrl(): ?string
    {
        $youtubeId = $this->youtubeId();

        return $youtubeId ? 'https://www.youtube.com/embed/'.$youtubeId : null;
    }

    public function resourceUrl(): ?string
    {
        if ($this->storage_type === 'upload') {
            return $this->publicUrl();
        }

        return $this->external_url;
    }

    public function resourceLabel(): string
    {
        if ($this->caption) {
            return $this->caption;
        }

        if ($this->storage_type === 'upload' && $this->file_path) {
            return basename($this->file_path);
        }

        if ($this->external_url) {
            return $this->external_url;
        }

        return 'Resource';
    }

    public function resourceKindLabel(): string
    {
        return match ($this->asset_type) {
            'document' => 'Document',
            'external_link' => 'External link',
            'video_link' => 'Video link',
            'file' => 'File',
            default => ucfirst(str_replace('_', ' ', $this->asset_type)),
        };
    }

    public static function parseYouTubeId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $patterns = [
            '/youtube\.com\/watch\?v=([A-Za-z0-9_-]+)/',
            '/youtu\.be\/([A-Za-z0-9_-]+)/',
            '/youtube\.com\/embed\/([A-Za-z0-9_-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    protected function resolveAssetUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'demo/')) {
            return asset($path);
        }

        return asset('storage/'.$path);
    }
}
