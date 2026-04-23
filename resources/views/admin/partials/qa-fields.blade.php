@php
    $qa = $qa ?? null;
    $translationsByLanguage = $translationsByLanguage ?? collect();
    $languages = $languages ?? \App\Support\LotgLanguage::supported();
    $qaType = old('qa_type', $qa?->qa_type ?? \App\Models\LawQa::TYPE_SIMPLE);
    $customAnswer = (bool) old('custom_answer', $qa?->hasCustomAnswer() ?? false);
    $existingOptionRows = $qa?->options
        ? $qa->options->map(function ($option) use ($languages) {
            $row = [
                'id' => $option->id,
                'is_correct' => $option->is_correct,
            ];

            foreach (array_keys($languages) as $languageCode) {
                $row['text_'.$languageCode] = $option->translations->firstWhere('language_code', $languageCode)?->text;
            }

            return $row;
        })->values()->all()
        : [];
    $optionRows = old('options', $existingOptionRows);
@endphp

<label>
    <span>Q&amp;A type</span>
    <select name="qa_type" data-qa-type-select>
        <option value="{{ \App\Models\LawQa::TYPE_SIMPLE }}" @selected($qaType === \App\Models\LawQa::TYPE_SIMPLE)>Simple Q&amp;A</option>
        <option value="{{ \App\Models\LawQa::TYPE_MULTIPLE_CHOICE }}" @selected($qaType === \App\Models\LawQa::TYPE_MULTIPLE_CHOICE)>Multiple choice</option>
    </select>
</label>

<label>
    <span>Sort order</span>
    <input type="number" min="1" name="sort_order" value="{{ old('sort_order', $qa?->sort_order ?? '') }}" placeholder="Leave blank to append as last">
</label>

<label class="checkbox-label">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $qa?->is_published ?? false))>
    <span>Published</span>
</label>

<section class="card section-card" data-qa-options-section>
    <div class="video-item-header">
        <div>
            <h3>Multiple-choice options</h3>
            <p class="nav-meta">Correct option text becomes the default displayed answer unless custom answer view is enabled.</p>
        </div>
        <button type="button" data-qa-option-add>Add option</button>
    </div>

    <div class="stack-form" data-qa-option-list>
        @foreach ($optionRows as $index => $option)
            <div class="card qa-option-card" data-qa-option-item>
                <div class="video-item-header">
                    <h4>Option <span data-qa-option-number>{{ $index + 1 }}</span></h4>
                    <button type="button" class="button-danger" data-qa-option-remove data-confirm-message="Remove this option row? Unsaved changes in this row will be lost.">Remove option</button>
                </div>
                <input type="hidden" name="options[{{ $index }}][id]" value="{{ $option['id'] ?? '' }}">
                <label class="checkbox-label">
                    <input type="checkbox" name="options[{{ $index }}][is_correct]" value="1" @checked((bool) ($option['is_correct'] ?? false))>
                    <span>Correct answer</span>
                </label>
                @foreach ($languages as $languageCode => $languageLabel)
                    <label>
                        <span>Option text {{ strtoupper($languageCode) }} - {{ $languageLabel }}</span>
                        <input type="text" name="options[{{ $index }}][text_{{ $languageCode }}]" value="{{ $option['text_'.$languageCode] ?? '' }}" @required($languageCode === 'id')>
                    </label>
                @endforeach
            </div>
        @endforeach
    </div>

    <template data-qa-option-template>
        <div class="card qa-option-card" data-qa-option-item>
            <div class="video-item-header">
                <h4>Option <span data-qa-option-number>__NUMBER__</span></h4>
                <button type="button" class="button-danger" data-qa-option-remove data-confirm-message="Remove this option row? Unsaved changes in this row will be lost.">Remove option</button>
            </div>
            <input type="hidden" name="options[__INDEX__][id]" value="">
            <label class="checkbox-label">
                <input type="checkbox" name="options[__INDEX__][is_correct]" value="1">
                <span>Correct answer</span>
            </label>
            @foreach ($languages as $languageCode => $languageLabel)
                <label>
                    <span>Option text {{ strtoupper($languageCode) }} - {{ $languageLabel }}</span>
                    <input type="text" name="options[__INDEX__][text_{{ $languageCode }}]" value="" @required($languageCode === 'id')>
                </label>
            @endforeach
        </div>
    </template>
</section>

<label class="checkbox-label" data-qa-custom-answer-toggle>
    <input type="checkbox" name="custom_answer" value="1" @checked($customAnswer) data-qa-custom-answer-input>
    <span>Use custom answer view instead of correct option text</span>
</label>

@foreach ($languages as $languageCode => $languageLabel)
    @php
        $translation = $translationsByLanguage[$languageCode] ?? null;
    @endphp
    <fieldset class="card section-card">
        <legend>{{ strtoupper($languageCode) }} - {{ $languageLabel }}</legend>

        <label>
            <span>Question</span>
            <input type="text" name="question_{{ $languageCode }}" value="{{ old('question_'.$languageCode, $translation?->question) }}" @required($languageCode === 'id')>
        </label>

        <label data-qa-answer-field>
            <span>Answer</span>
            <textarea name="answer_html_{{ $languageCode }}" rows="8">{{ old('answer_html_'.$languageCode, $translation?->answer_html) }}</textarea>
        </label>

        <label>
            <span>Translation status</span>
            <select name="translation_status_{{ $languageCode }}">
                <option value="draft" @selected(old('translation_status_'.$languageCode, $translation?->status ?? 'draft') === 'draft')>Draft</option>
                <option value="published" @selected(old('translation_status_'.$languageCode, $translation?->status ?? ($languageCode === 'id' ? 'published' : 'draft')) === 'published')>Published</option>
            </select>
        </label>
    </fieldset>
@endforeach
