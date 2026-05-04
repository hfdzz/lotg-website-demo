@php
    $isEditing = isset($media) && $media;
    $assetType = old('asset_type', $media?->asset_type ?? 'image');
    $videoSource = old('video_source', $media?->asset_type === 'video' && $media?->storage_type === 'upload' ? 'upload' : 'youtube');
    $videoUploadDisks = collect(config('lotg.media_upload_disks', ['public', 's3']))
        ->map(fn ($disk) => trim((string) $disk))
        ->filter(fn ($disk) => $disk !== '' && config('filesystems.disks.'.$disk))
        ->unique()
        ->values();
    $selectedUploadDisk = old(
        'upload_disk',
        $media?->storage_type === 'upload'
            ? ($media->storage_disk ?: config('lotg.media_default_upload_disk', 'public'))
            : config('lotg.media_default_upload_disk', 'public')
    );
    $videoUploadMaxMb = max((int) config('lotg.video_upload_max_kb', 51200) / 1024, 1);
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
        <input type="file" name="image_file" accept=".jpg,.jpeg,.png,.gif,.webp,.avif,.svg,image/jpeg,image/png,image/gif,image/webp,image/avif,image/svg+xml">
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
        <div class="law-meta">Video source</div>
        <select name="video_source" data-video-source-select>
            <option value="upload" @selected($videoSource === 'upload')>Uploaded file</option>
            <option value="youtube" @selected($videoSource === 'youtube')>YouTube URL</option>
        </select>
    </label>

    <div data-video-source-section="upload" @if($videoSource !== 'upload') hidden @endif>
        <label>
            <div class="law-meta">{{ $isEditing && $media?->asset_type === 'video' && $media?->storage_type === 'upload' ? 'Replace MP4 file' : 'MP4 file' }}</div>
            <input type="file" name="video_file" accept=".mp4,video/mp4">
        </label>

        <label>
            <div class="law-meta">Upload disk</div>
            <select name="upload_disk">
                @foreach ($videoUploadDisks as $uploadDisk)
                    <option value="{{ $uploadDisk }}" @selected($selectedUploadDisk === $uploadDisk)>{{ strtoupper($uploadDisk) }}</option>
                @endforeach
            </select>
        </label>

        <div class="nav-meta">Uploaded video currently supports MP4 only. Maximum file size: {{ rtrim(rtrim(number_format($videoUploadMaxMb, 2), '0'), '.') }} MB.</div>

        @if ($isEditing && $media?->asset_type === 'video' && $media?->storage_type === 'upload')
            <label>
                <div class="law-meta">Current file</div>
                <input type="text" value="{{ $media->file_path }}" disabled>
            </label>

            <label>
                <div class="law-meta">Current disk</div>
                <input type="text" value="{{ strtoupper($media->storage_disk ?: 'public') }}" disabled>
            </label>
        @endif
    </div>

    <div data-video-source-section="youtube" @if($videoSource !== 'youtube') hidden @endif>
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
</div>

<label>
    <div class="law-meta">Caption</div>
    <input type="text" name="caption" value="{{ old('caption', $media?->caption) }}">
</label>

<label>
    <div class="law-meta">Credit / attribution</div>
    <input type="text" name="credit" value="{{ old('credit', $media?->credit) }}">
</label>
