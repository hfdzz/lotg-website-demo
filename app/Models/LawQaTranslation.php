<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LawQaTranslation extends Model
{
    protected $fillable = [
        'law_qa_id',
        'language_code',
        'question',
        'answer_html',
        'status',
    ];

    public function lawQa(): BelongsTo
    {
        return $this->belongsTo(LawQa::class);
    }
}
