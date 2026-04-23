<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LawQaOptionTranslation extends Model
{
    protected $fillable = [
        'option_id',
        'language_code',
        'text',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(LawQaOption::class, 'option_id');
    }
}
