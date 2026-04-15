<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTranslation extends Model
{
    protected $fillable = [
        'document_id',
        'language_code',
        'title',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
