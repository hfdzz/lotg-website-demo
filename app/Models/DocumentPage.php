<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
