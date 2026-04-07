<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LawQa extends Model
{
    protected $fillable = [
        'law_id',
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'id' => 'integer',
        'law_id' => 'integer',
        'sort_order' => 'integer',
        'is_published' => 'boolean',
    ];

    public function law(): BelongsTo
    {
        return $this->belongsTo(Law::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(LawQaTranslation::class)->orderBy('language_code');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function translationFor(?string $languageCode = null): ?LawQaTranslation
    {
        $languageCode = $languageCode ?: app()->getLocale();
        $translation = $this->translations->firstWhere('language_code', $languageCode);

        if ($translation?->status === 'published') {
            return $translation;
        }

        $fallback = $this->translations->firstWhere('language_code', config('app.fallback_locale'));

        if ($fallback?->status === 'published') {
            return $fallback;
        }

        return $this->translations->first(fn (LawQaTranslation $item) => $item->status === 'published')
            ?? $translation
            ?? $fallback
            ?? $this->translations->first();
    }

    public function displayQuestion(?string $languageCode = null): string
    {
        return $this->translationFor($languageCode)?->question ?: 'Q&A #'.$this->id;
    }

    public function displayAnswer(?string $languageCode = null): ?string
    {
        return $this->translationFor($languageCode)?->answer_html;
    }
}
