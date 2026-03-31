@php
    $mediaAssets = $node?->mediaAssets ?? collect();
    $imageAsset = $mediaAssets->firstWhere('asset_type', 'image');
    $videoUrls = $mediaAssets
        ->where('asset_type', 'video')
        ->pluck('external_url')
        ->filter()
        ->implode("\n");
@endphp

<label>
    <div class="law-meta">Parent node</div>
    <div class="nav-meta">Choose the section this node belongs under. Leave it at root for top-level content.</div>
    <select name="parent_id">
        <option value="">Root level</option>
        @foreach ($parentOptions as $option)
            <option value="{{ $option['id'] }}" @selected((string) old('parent_id', $node?->parent_id) === (string) $option['id'])>{{ $option['label'] }}</option>
        @endforeach
    </select>
</label>

<label>
    <div class="law-meta">Node type</div>
    <div class="nav-meta">Use `section` for headings, `rich_text` for prose, `image` for a single image block, and `video_group` for one or more video links.</div>
    <select name="node_type">
        @foreach (['section', 'rich_text', 'image', 'video_group'] as $type)
            <option value="{{ $type }}" @selected(old('node_type', $node?->node_type ?? 'section') === $type)>{{ $type }}</option>
        @endforeach
    </select>
</label>

<label>
    <div class="law-meta">Sort order</div>
    <div class="nav-meta">Lower numbers appear first among sibling nodes under the same parent.</div>
    <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $node?->sort_order ?? 0) }}">
</label>

<label>
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $node?->is_published ?? true))>
    Publish this node
</label>

<div class="nav-meta">If unpublished, the node stays in admin but does not appear on the public law page.</div>

<label>
    <div class="law-meta">Title (EN)</div>
    <input type="text" name="title" value="{{ old('title', $translation?->title) }}">
</label>

<label>
    <div class="law-meta">Body HTML (EN)</div>
    <textarea name="body_html" rows="10">{{ old('body_html', $translation?->body_html) }}</textarea>
</label>

<label>
    <div class="law-meta">Translation status</div>
    <select name="translation_status">
        @foreach (['draft', 'published'] as $status)
            <option value="{{ $status }}" @selected(old('translation_status', $translation?->status ?? 'published') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
</label>

<div class="card">
    <h3>Image fields</h3>
    <p class="nav-meta">Used only when the node type is `image`.</p>
    <label>
        <div class="law-meta">Upload image</div>
        <input type="file" name="image_file" accept="image/*">
    </label>

    <label>
        <div class="law-meta">Current image</div>
        <input type="text" value="{{ $imageAsset?->file_path }}" disabled>
    </label>

    <label>
        <div class="law-meta">Image caption</div>
        <input type="text" name="image_caption" value="{{ old('image_caption', $imageAsset?->caption) }}">
    </label>

    <label>
        <div class="law-meta">Image credit</div>
        <input type="text" name="image_credit" value="{{ old('image_credit', $imageAsset?->credit) }}">
    </label>
</div>

<div class="card">
    <h3>Video fields</h3>
    <p class="nav-meta">Used only when the node type is `video_group`.</p>
    <label>
        <div class="law-meta">YouTube URLs, one per line</div>
        <textarea name="video_urls" rows="5">{{ old('video_urls', $videoUrls) }}</textarea>
    </label>
</div>

@if ($errors->any())
    <div class="empty-state">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
