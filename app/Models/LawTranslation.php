<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LawTranslation extends Model
{
    protected $fillable = [
        'law_id',
        'language_code',
        'title',
        'subtitle',
        'description_text',
    ];

    public function law(): BelongsTo
    {
        return $this->belongsTo(Law::class);
    }
}
