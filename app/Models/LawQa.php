<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LawQa extends Model
{
    public const TYPE_SIMPLE = 'simple';

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    protected $fillable = [
        'law_id',
        'qa_type',
        'sort_order',
        'is_published',
        'uses_custom_answer',
    ];

    protected $casts = [
        'id' => 'integer',
        'law_id' => 'integer',
        'sort_order' => 'integer',
        'is_published' => 'boolean',
        'uses_custom_answer' => 'boolean',
    ];

    public function law(): BelongsTo
    {
        return $this->belongsTo(Law::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(LawQaTranslation::class)->orderBy('language_code');
    }

    public function options(): HasMany
    {
        return $this->hasMany(LawQaOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function translationFor(?string $languageCode = null): ?LawQaTranslation
    {
        $languageCode = $languageCode ?: app()->getLocale();
        $translation = $this->translations->firstWhere('language_code', $languageCode);

        if ($translation?->status === 'published') {
            return $translation;
        }

        $fallback = $this->translations->firstWhere('language_code', config('app.fallback_locale'));

        if ($fallback?->status === 'published') {
            return $fallback;
        }

        return $this->translations->first(fn (LawQaTranslation $item) => $item->status === 'published')
            ?? $translation
            ?? $fallback
            ?? $this->translations->first();
    }

    public function displayQuestion(?string $languageCode = null): string
    {
        return $this->translationFor($languageCode)?->question ?: 'Q&A #'.$this->id;
    }

    public function displayAnswer(?string $languageCode = null): ?string
    {
        $translation = $this->translationFor($languageCode);

        if ($this->qa_type !== self::TYPE_MULTIPLE_CHOICE || $this->uses_custom_answer) {
            return $translation?->answer_html;
        }

        return $this->correctOptionsAnswerHtml($languageCode);
    }

    public function hasCustomAnswer(): bool
    {
        if ($this->uses_custom_answer) {
            return true;
        }

        if ($this->qa_type !== self::TYPE_MULTIPLE_CHOICE) {
            return false;
        }

        return $this->translations->contains(fn (LawQaTranslation $translation) => filled($translation->answer_html));
    }

    public function isMultipleChoice(): bool
    {
        return $this->qa_type === self::TYPE_MULTIPLE_CHOICE;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{text: string, is_correct: bool}>
     */
    public function optionsForDisplay(?string $languageCode = null)
    {
        $options = $this->relationLoaded('options')
            ? $this->options
            : $this->options()->with('translations')->get();

        return $options
            ->map(fn (LawQaOption $option) => [
                'text' => $option->displayText($languageCode),
                'is_correct' => (bool) $option->is_correct,
            ])
            ->filter(fn (array $option) => trim($option['text']) !== '')
            ->values();
    }

    protected function correctOptionsAnswerHtml(?string $languageCode = null): ?string
    {
        $texts = $this->optionsForDisplay($languageCode)
            ->filter(fn (array $option) => $option['is_correct'])
            ->pluck('text')
            ->filter(fn (string $text) => trim($text) !== '')
            ->values();

        if ($texts->isEmpty()) {
            return null;
        }

        if ($texts->count() === 1) {
            return '<p>'.e($texts->first()).'</p>';
        }

        return '<ul>'.$texts
            ->map(fn (string $text) => '<li>'.e($text).'</li>')
            ->implode('').'</ul>';
    }
}
