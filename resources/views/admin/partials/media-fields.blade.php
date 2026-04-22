@php
    $isEditing = isset($media) && $media;
    $assetType = old('asset_type', $media?->asset_type ?? 'image');
@endphp

@if (! $isEditing)
    <label>
        <div class="law-meta">Media type</div>
        <select name="asset_type" data-media-type-select>
            <option value="image" @selected($assetType === 'image')>Image</option>
            <option value="video" @selected($assetType === 'video')>Video</option>
        </select>
    </label>
@else
    <label>
        <div class="law-meta">Media type</div>
        <input type="text" value="{{ ucfirst($media->asset_type) }}" disabled>
    </label>
@endif

<div @if (! $isEditing) data-media-type-section="image" @elseif($media->asset_type !== 'image') hidden @endif>
    <label>
        <div class="law-meta">{{ $isEditing && $media->asset_type === 'image' ? 'Replace image file' : 'Image file' }}</div>
        <input type="file" name="media_file" accept=".jpg,.jpeg,.png,.gif,.webp,.avif,.svg,image/jpeg,image/png,image/gif,image/webp,image/avif,image/svg+xml">
    </label>

    @if ($isEditing && $media->asset_type === 'image')
        <label>
            <div class="law-meta">Current file</div>
            <input type="text" value="{{ $media->file_path }}" disabled>
        </label>
    @endif
</div>

<div @if (! $isEditing) data-media-type-section="video" @elseif($media->asset_type !== 'video') hidden @endif>
    <label>
        <div class="law-meta">YouTube URL</div>
        <input
            type="url"
            name="external_url"
            value="{{ old('external_url', $media?->external_url) }}"
            placeholder="https://www.youtube.com/watch?v=..."
        >
    </label>
</div>

<label>
    <div class="law-meta">Caption</div>
    <input type="text" name="caption" value="{{ old('caption', $media?->caption) }}">
</label>

<label>
    <div class="law-meta">Credit / attribution</div>
    <input type="text" name="credit" value="{{ old('credit', $media?->credit) }}">
</label>
