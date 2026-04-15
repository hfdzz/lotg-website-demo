<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentPageTranslation extends Model
{
    protected $fillable = [
        'document_page_id',
        'language_code',
        'title',
        'body_html',
    ];

    public function documentPage(): BelongsTo
    {
        return $this->belongsTo(DocumentPage::class);
    }
}
