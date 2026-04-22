<?php

namespace App\Models;

use App\Support\LotgLanguage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function mediaAssets(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class, 'document_page_media')
            ->withPivot(['id', 'media_key', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderByPivot('id');
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

    public function renderBodyWithMedia(?string $languageCode = null): ?string
    {
        $body = $this->displayBody($languageCode);

        if (! $body) {
            return null;
        }

        $this->loadMissing('mediaAssets');

        return preg_replace_callback('/\{\{\s*media:([A-Za-z0-9_-]+)\s*\}\}/', function (array $matches): string {
            $mediaKey = $matches[1];
            $mediaAsset = $this->mediaAssets->first(
                fn (MediaAsset $asset) => $asset->asset_type === 'image' && $asset->pivot?->media_key === $mediaKey
            );

            if (! $mediaAsset || ! $mediaAsset->publicUrl()) {
                return '';
            }

            $caption = trim((string) $mediaAsset->caption);
            $credit = trim((string) $mediaAsset->credit);
            $captionHtml = '';

            if ($caption !== '' || $credit !== '') {
                $captionHtml = '<figcaption>'
                    .e($caption)
                    .($caption !== '' && $credit !== '' ? ' ' : '')
                    .($credit !== '' ? '<span class="media-credit">'.e($credit).'</span>' : '')
                    .'</figcaption>';
            }

            return '<figure class="document-inline-media">'
                .'<img src="'.e($mediaAsset->publicUrl()).'" alt="'.e($caption ?: $mediaKey).'" loading="lazy">'
                .$captionHtml
                .'</figure>';
        }, $body);
    }
}
