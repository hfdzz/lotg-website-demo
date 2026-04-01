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
}
