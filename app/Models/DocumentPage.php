<?php

namespace App\Models;

use App\Support\LotgLanguage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentPage extends Model
{
    protected $fillable = [
        'document_id',
        'slug',
        'title',
        'body_html',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
        'document_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(DocumentPageTranslation::class)->orderBy('language_code');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function translationFor(?string $languageCode = null): ?DocumentPageTranslation
    {
        $languageCode = $languageCode ?: app()->getLocale();
        $translation = $this->translations->firstWhere('language_code', LotgLanguage::normalize($languageCode));

        return $translation
            ?? $this->translations->firstWhere('language_code', config('app.fallback_locale'))
            ?? $this->translations->firstWhere('language_code', LotgLanguage::default())
            ?? $this->translations->first();
    }

    public function displayTitle(?string $languageCode = null): string
    {
        return $this->translationFor($languageCode)?->title ?: $this->title;
    }

    public function displayBody(?string $languageCode = null): ?string
    {
        return $this->translationFor($languageCode)?->body_html ?: $this->body_html;
    }
}
