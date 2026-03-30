<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function publishedContentNodes(): HasMany
    {
        return $this->contentNodes()->where('is_published', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function displayTitle(): string
    {
        $prefix = 'law-'.$this->law_number.'-';

        if ($this->slug && str_starts_with($this->slug, $prefix)) {
            return str($this->slug)
                ->after($prefix)
                ->replace('-', ' ')
                ->title()
                ->value();
        }

        if ($this->slug) {
            return str($this->slug)
                ->replace('-', ' ')
                ->title()
                ->value();
        }

        return 'Law '.$this->law_number;
    }
}
