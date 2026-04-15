<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'edition_id',
        'slug',
        'title',
        'type',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
        'edition_id' => 'integer',
        'sort_order' => 'integer',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(DocumentPage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function publishedPages(): HasMany
    {
        return $this->pages()->where('status', 'published');
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

    public function firstPublishedPage(): ?DocumentPage
    {
        return $this->relationLoaded('publishedPages')
            ? $this->publishedPages->first()
            : $this->publishedPages()->first();
    }

    public function isCollection(): bool
    {
        return $this->type === 'collection';
    }
}
