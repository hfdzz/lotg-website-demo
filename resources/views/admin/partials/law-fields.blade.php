@php
    $translationsByLanguage = $translationsByLanguage ?? collect();
    $languages = $languages ?? \App\Support\LotgLanguage::supported();
    $selectedEdition = $selectedEdition ?? null;
@endphp

<label>
    <div class="law-meta">Law number</div>
    <input type="text" name="law_number" value="{{ old('law_number', $law?->law_number) }}">
</label>

<label>
    <div class="law-meta">Slug</div>
    <input type="text" name="slug" value="{{ old('slug', $law?->slug) }}">
</label>

<label>
    <div class="law-meta">Sort order</div>
    <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $law?->sort_order ?? 0) }}">
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
            <input type="text" name="title_{{ $languageCode }}" value="{{ old('title_'.$languageCode, $translation?->title) }}">
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

@if ($errors->any())
    <div class="empty-state">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
