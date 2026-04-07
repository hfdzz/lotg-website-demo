<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'type',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
        'sort_order' => 'integer',
    ];

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
