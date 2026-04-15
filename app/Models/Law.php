<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Law extends Model
{
    protected $fillable = [
        'edition_id',
        'law_number',
        'slug',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
        'edition_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function contentNodes(): HasMany
    {
        return $this->hasMany(ContentNode::class);
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(LawTranslation::class)->orderBy('language_code');
    }

    public function qas(): HasMany
    {
        return $this->hasMany(LawQa::class);
    }

    public function publishedQas(): HasMany
    {
        return $this->hasMany(LawQa::class)->where('is_published', true);
    }

    public function publishedContentNodes(): HasMany
    {
        return $this->contentNodes()->where('is_published', true);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== 'slug') {
            return parent::resolveRouteBinding($value, $field);
        }

        $slugQuery = $this->newQuery()->where('slug', (string) $value);
        $requestedEditionId = request()?->integer('edition');

        if ($requestedEditionId) {
            return (clone $slugQuery)
                ->where('edition_id', $requestedEditionId)
                ->first();
        }

        $activeEditionId = Edition::current()?->id;

        if ($activeEditionId) {
            return (clone $slugQuery)
                ->where('edition_id', $activeEditionId)
                ->first();
        }

        return null;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeForEdition(Builder $query, ?int $editionId): Builder
    {
        if (! $editionId) {
            return $query;
        }

        return $query->where('edition_id', $editionId);
    }

    public function scopeForActiveEdition(Builder $query): Builder
    {
        return $query->where('edition_id', Edition::current()?->id);
    }

    public function translationFor(?string $languageCode = null): ?LawTranslation
    {
        $languageCode = $languageCode ?: app()->getLocale();
        $translation = $this->translations->firstWhere('language_code', $languageCode);

        return $translation ?? $this->translations->firstWhere('language_code', config('app.fallback_locale')) ?? $this->translations->first();
    }

    public function displayTitle(?string $languageCode = null): string
    {
        $translation = $this->translationFor($languageCode);

        if ($translation?->title) {
            return $translation->title;
        }

        $prefix = 'law-'.$this->law_number.'-';

        if ($this->slug && str_starts_with($this->slug, $prefix)) {
            return Str::of($this->slug)
                ->after($prefix)
                ->replace('-', ' ')
                ->title()
                ->value();
        }

        if ($this->slug) {
            return Str::of($this->slug)
                ->replace('-', ' ')
                ->title()
                ->value();
        }

        return 'Law '.$this->law_number;
    }

    public function displaySubtitle(?string $languageCode = null): ?string
    {
        return $this->translationFor($languageCode)?->subtitle;
    }

    public function displayDescription(?string $languageCode = null): ?string
    {
        return $this->translationFor($languageCode)?->description_text;
    }

    public function cardBackgroundImageUrl(): string
    {
        $relativePath = 'statics/law-images/law'.$this->law_number.'.jpg';

        if (file_exists(public_path($relativePath))) {
            return asset($relativePath);
        }
        return 'statics/law-images/law'.$this->law_number.'.webp';
    }
}
