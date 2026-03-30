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

@if ($errors->any())
    <div class="empty-state">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
