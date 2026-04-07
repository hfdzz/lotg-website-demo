@php
    $qa = $qa ?? null;
    $translationsByLanguage = $translationsByLanguage ?? collect();
    $languages = $languages ?? \App\Support\LotgLanguage::supported();
@endphp

<label>
    <span>Sort order</span>
    <input type="number" min="1" name="sort_order" value="{{ old('sort_order', $qa?->sort_order ?? '') }}" placeholder="Leave blank to append as last">
</label>

<label class="checkbox-label">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $qa?->is_published ?? false))>
    <span>Published</span>
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

        <label>
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
