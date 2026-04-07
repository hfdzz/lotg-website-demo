@php
    $translationsByLanguage = $translationsByLanguage ?? collect();
    $languages = $languages ?? \App\Support\LotgLanguage::supported();
    $selectedEdition = $selectedEdition ?? null;
    $lawNumberValue = old('law_number', $law?->law_number);
    $slugValue = old('slug', $law?->slug);
    $titleIdValue = old('title_id', $translationsByLanguage->get('id')?->title);
    $slugPreview = \Illuminate\Support\Str::slug($slugValue ?: ($titleIdValue ?: ''));
@endphp

<label>
    <div class="law-meta">Law number</div>
    <div class="nav-meta">Leave blank to append as the last law number in this edition.</div>
    <input type="text" name="law_number" value="{{ $lawNumberValue }}" data-law-number-input>
</label>

<label>
    <div class="law-meta">Slug</div>
    <div class="nav-meta">The saved slug is always normalized. Leave this blank to auto-generate it from the Indonesian title.</div>
    <input type="text" name="slug" value="{{ $slugValue }}" data-law-slug-input>
    <div class="nav-meta">Result: <span data-law-slug-preview>{{ $slugPreview ?: '-' }}</span></div>
</label>

<label>
    <div class="law-meta">Sort order</div>
    <div class="nav-meta">Leave blank to append as the last law in this edition.</div>
    <input type="number" min="1" name="sort_order" value="{{ old('sort_order', $law?->sort_order ?? '') }}">
</label>

<label>
    <div class="law-meta">Status</div>
    <select name="status">
        @foreach (['draft', 'published'] as $status)
            <option value="{{ $status }}" @selected(old('status', $law?->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
</label>

@foreach ($languages as $languageCode => $languageLabel)
    @php
        $translation = $translationsByLanguage->get($languageCode);
    @endphp

    <div class="card">
        <h3>{{ $languageLabel }} law content</h3>

        <label>
            <div class="law-meta">Title ({{ strtoupper($languageCode) }})</div>
            <input type="text" name="title_{{ $languageCode }}" value="{{ old('title_'.$languageCode, $translation?->title) }}" @if($languageCode === 'id') data-law-title-id-input @endif>
        </label>

        <label>
            <div class="law-meta">Subtitle ({{ strtoupper($languageCode) }})</div>
            <input type="text" name="subtitle_{{ $languageCode }}" value="{{ old('subtitle_'.$languageCode, $translation?->subtitle) }}">
        </label>

        <label>
            <div class="law-meta">Description text ({{ strtoupper($languageCode) }})</div>
            <textarea name="description_text_{{ $languageCode }}" rows="4">{{ old('description_text_'.$languageCode, $translation?->description_text) }}</textarea>
        </label>
    </div>
@endforeach
