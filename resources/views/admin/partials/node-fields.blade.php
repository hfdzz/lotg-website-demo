@php
    $mediaAssets = $node?->mediaAssets ?? collect();
    $isEditing = (bool) $node;
    $imageAsset = $mediaAssets->firstWhere('asset_type', 'image');
    $translationsByLanguage = $translationsByLanguage ?? collect();
    $languages = $languages ?? \App\Support\LotgLanguage::supported();
    $videoUrls = $mediaAssets
        ->where('asset_type', 'video')
        ->pluck('external_url')
        ->filter()
        ->implode("\n");
    $videoAssets = $mediaAssets
        ->where('asset_type', 'video')
        ->values();
    $resourceLineItems = $mediaAssets
        ->whereIn('asset_type', ['document', 'external_link', 'video_link'])
        ->map(function ($asset) {
            $label = $asset->caption ?: $asset->external_url;
            $type = $asset->asset_type;

            return $type.' | '.$label.' | '.$asset->external_url;
        })
        ->implode("\n");
    $uploadedResourceAssets = $mediaAssets
        ->whereIn('asset_type', ['document', 'file'])
        ->where('storage_type', 'upload')
        ->values();
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
    <div class="nav-meta">Use `section` for headings, `rich_text` for prose, `image` for a single image block, `video_group` for embedded videos, and `resource_list` for linked-only references and files.</div>
    <select name="node_type" data-node-type-select>
        @foreach (['section', 'rich_text', 'image', 'video_group', 'resource_list'] as $type)
            <option value="{{ $type }}" @selected(old('node_type', $node?->node_type ?? 'section') === $type)>{{ $type }}</option>
        @endforeach
    </select>
</label>

<label>
    <div class="law-meta">Sort order</div>
    <div class="nav-meta">
        @if ($isEditing)
            Use 1-based ordering. Moving this node to a new number will shift affected siblings automatically.
        @else
            New nodes are appended automatically as the last sibling under the selected parent.
        @endif
    </div>
    <input type="number" min="1" name="sort_order" value="{{ old('sort_order', $node?->sort_order ?? 1) }}">
</label>

<label>
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $node?->is_published ?? true))>
    Publish this node
</label>

<div class="nav-meta">If unpublished, the node stays in admin but does not appear on the public law page.</div>

<div class="card admin-translation-card" data-translation-editor>
    <div class="translation-toolbar">
        <label>
            <div class="law-meta">Translation</div>
            <select data-translation-select>
                @foreach ($languages as $languageCode => $languageLabel)
                    <option value="{{ $languageCode }}">{{ $languageLabel }} ({{ strtoupper($languageCode) }})</option>
                @endforeach
            </select>
        </label>
        <div class="nav-meta">An asterisk appears if title, body, or status has changed in that translation.</div>
    </div>

    @foreach ($languages as $languageCode => $languageLabel)
        @php
            $translation = $translationsByLanguage->get($languageCode);
            $titleValue = old('title_'.$languageCode, $translation?->title);
            $bodyValue = old('body_html_'.$languageCode, $translation?->body_html);
            $statusValue = old('translation_status_'.$languageCode, $translation?->status ?? 'published');
        @endphp

        <div class="translation-panel" data-translation-panel="{{ $languageCode }}">
            <h3>{{ $languageLabel }} translation</h3>

            <label>
                <div class="law-meta">Title ({{ strtoupper($languageCode) }})</div>
                <input
                    type="text"
                    name="title_{{ $languageCode }}"
                    value="{{ $titleValue }}"
                    data-translation-field="{{ $languageCode }}"
                    data-initial-value="{{ $titleValue }}"
                >
            </label>

            <label>
                <div class="law-meta">Body HTML ({{ strtoupper($languageCode) }})</div>
                <textarea
                    name="body_html_{{ $languageCode }}"
                    rows="10"
                    data-translation-field="{{ $languageCode }}"
                    data-initial-value="{{ $bodyValue }}"
                >{{ $bodyValue }}</textarea>
            </label>

            <label>
                <div class="law-meta">Translation status ({{ strtoupper($languageCode) }})</div>
                <select
                    name="translation_status_{{ $languageCode }}"
                    data-translation-field="{{ $languageCode }}"
                    data-initial-value="{{ $statusValue }}"
                >
                    @foreach (['draft', 'published'] as $status)
                        <option value="{{ $status }}" @selected($statusValue === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    @endforeach
</div>

<div class="card" data-node-type-section="image">
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

<div class="card" data-node-type-section="video_group">
    <h3>Video fields</h3>
    <p class="nav-meta">Used only when the node type is `video_group`.</p>
    <label>
        <div class="law-meta">YouTube URLs, one per line</div>
        <textarea name="video_urls" rows="5">{{ old('video_urls', $videoUrls) }}</textarea>
    </label>
    <div class="stack-form">
        @for ($index = 0; $index < 6; $index++)
            @php
                $existingVideo = $videoAssets->get($index);
            @endphp
            <div class="card">
                <h4>Video {{ $index + 1 }}</h4>
                <label>
                    <div class="law-meta">Caption</div>
                    <input type="text" name="video_captions[]" value="{{ old('video_captions.'.$index, $existingVideo?->caption) }}">
                </label>
                <label>
                    <div class="law-meta">Credit / attribution</div>
                    <input type="text" name="video_credits[]" value="{{ old('video_credits.'.$index, $existingVideo?->credit) }}">
                </label>
            </div>
        @endfor
    </div>
</div>

<div class="card" data-node-type-section="resource_list">
    <h3>Resource list fields</h3>
    <p class="nav-meta">Used only when the node type is `resource_list`.</p>
    <label>
        <div class="law-meta">Linked resources</div>
        <div class="nav-meta">One per line. Format: `type | label | url` or `label | url`. Types can be `document`, `external_link`, or `video_link`.</div>
        <textarea name="resource_lines" rows="6">{{ old('resource_lines', $resourceLineItems) }}</textarea>
    </label>

    <label>
        <div class="law-meta">Upload files</div>
        <div class="nav-meta">Files added here appear as downloadable links in the resource list.</div>
        <input type="file" name="resource_files[]" multiple>
    </label>

    @if ($uploadedResourceAssets->isNotEmpty())
        <div>
            <div class="law-meta">Existing uploaded files</div>
            <div class="stack-top">
                @foreach ($uploadedResourceAssets as $asset)
                    <label>
                        <input type="checkbox" name="remove_resource_asset_ids[]" value="{{ $asset->id }}">
                        Remove {{ $asset->caption ?: basename($asset->file_path) }}
                    </label>
                @endforeach
            </div>
        </div>
    @endif
</div>

@if ($errors->any())
    <div class="empty-state">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
