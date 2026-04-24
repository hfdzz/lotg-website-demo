<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// Legacy lightweight update/feed model. Official edition Law Changes content should live in documents.
class ChangelogEntry extends Model
{
    protected $fillable = [
        'edition_id',
        'language_code',
        'title',
        'body',
        'sort_order',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }
}
