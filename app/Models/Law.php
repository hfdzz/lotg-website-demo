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
}
