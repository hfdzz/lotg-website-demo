<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LawQaOption extends Model
{
    protected $fillable = [
        'law_qa_id',
        'sort_order',
        'is_correct',
    ];

    protected $casts = [
        'id' => 'integer',
        'law_qa_id' => 'integer',
        'sort_order' => 'integer',
        'is_correct' => 'boolean',
    ];

    public function lawQa(): BelongsTo
    {
        return $this->belongsTo(LawQa::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(LawQaOptionTranslation::class, 'option_id')->orderBy('language_code');
    }

    public function translationFor(?string $languageCode = null): ?LawQaOptionTranslation
    {
        $languageCode = $languageCode ?: app()->getLocale();

        return $this->translations->firstWhere('language_code', $languageCode)
            ?? $this->translations->firstWhere('language_code', config('app.fallback_locale'))
            ?? $this->translations->first();
    }

    public function displayText(?string $languageCode = null): string
    {
        return $this->translationFor($languageCode)?->text ?: 'Option #'.$this->id;
    }
}
