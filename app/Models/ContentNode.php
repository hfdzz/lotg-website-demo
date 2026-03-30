<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentNode extends Model
{
    protected $fillable = [
        'law_id',
        'parent_id',
        'node_type',
        'sort_order',
        'is_published',
        'settings_json',
    ];

    protected $casts = [
        'id' => 'integer',
        'law_id' => 'integer',
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'is_published' => 'boolean',
        'settings_json' => 'array',
    ];

    public function law(): BelongsTo
    {
        return $this->belongsTo(Law::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ContentNodeTranslation::class)->orderBy('language_code');
    }

    public function mediaAssets(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class, 'content_node_media')
            ->withPivot(['sort_order'])
            ->withTimestamps()
            ->orderBy('content_node_media.sort_order');
    }

    public function translationFor(string $languageCode): ?ContentNodeTranslation
    {
        $translation = $this->translations->firstWhere('language_code', $languageCode);

        return $translation ?? $this->translations->first();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
