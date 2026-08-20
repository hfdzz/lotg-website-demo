@extends('layouts.app')

@section('title', 'Admin | Media')

@section('content')
    <a class="back-link" href="{{ route('admin.home') }}">Back to admin home</a>

    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Manage media</h1>
        <p>Reusable image and video assets for law nodes and document pages. Editing a shared asset updates every place that uses it.</p>
    </section>

    @if (session('status'))
        <div class="card surface-note flash-message flash-message-success">
            <strong>{{ session('status') }}</strong>
        </div>
    @endif

    @if ($errors->any())
        <div class="flash-message-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @php
        $bulkItems = old('items');

        if (! is_array($bulkItems) || $bulkItems === []) {
            $bulkItems = [[]];
        }
    @endphp

    <details class="card collapse-card media-add-card" @if($errors->any()) open @endif>
        <summary class="collapse-summary">
            <h2>Add media</h2>
        </summary>
        <div class="collapse-body">
            <form action="{{ route('admin.media.store') }}" method="post" enctype="multipart/form-data" class="stack-form" data-media-bulk-editor>
                @csrf
                <div class="media-add-card-body">
                    <div class="media-bulk-list" data-media-bulk-list>
                        @foreach ($bulkItems as $index => $itemValues)
                            <section class="card media-bulk-item stack-form" data-media-bulk-item>
                                <div class="media-bulk-item-header">
                                    <h3>Media <span data-media-bulk-number>{{ $loop->iteration }}</span></h3>
                                    <button type="button" data-media-bulk-remove>Remove</button>
                                </div>
                                @include('admin.partials.media-fields', [
                                    'media' => null,
                                    'fieldPrefix' => 'items['.$index.']',
                                    'oldPrefix' => 'items.'.$index,
                                    'fieldValues' => is_array($itemValues) ? $itemValues : [],
                                ])
                            </section>
                        @endforeach
                    </div>
                </div>
                <template data-media-bulk-template>
                    <section class="card media-bulk-item stack-form" data-media-bulk-item>
                        <div class="media-bulk-item-header">
                            <h3>Media <span data-media-bulk-number>1</span></h3>
                            <button type="button" data-media-bulk-remove>Remove</button>
                        </div>
                        @include('admin.partials.media-fields', [
                            'media' => null,
                            'fieldPrefix' => 'items[__INDEX__]',
                            'oldPrefix' => 'items.__INDEX__',
                            'fieldValues' => [],
                        ])
                    </section>
                </template>
                <div class="media-bulk-actions">
                    <button type="button" data-media-bulk-add>Add another media</button>
                    <button type="submit">Create media</button>
                </div>
            </form>
        </div>
    </details>

    <section class="card media-admin-section-card">
        <h2>Existing media</h2>
        <form action="{{ route('admin.media.index') }}" method="get" class="media-filter-form stack-top">
            <label>
                <div class="law-meta">Media type</div>
                <select name="media_type" onchange="this.form.submit()">
                    <option value="all" @selected($selectedMediaType === 'all')>All</option>
                    <option value="image" @selected($selectedMediaType === 'image')>Image</option>
                    <option value="video" @selected($selectedMediaType === 'video')>Video</option>
                </select>
            </label>
            <label>
                <div class="law-meta">Where used</div>
                <select name="usage_filter" onchange="this.form.submit()">
                    <option value="all" @selected($selectedUsageFilter === 'all')>All</option>
                    <option value="any" @selected($selectedUsageFilter === 'any')>Any</option>
                    <option value="published" @selected($selectedUsageFilter === 'published')>Published nodes</option>
                    <option value="active" @selected($selectedUsageFilter === 'active')>Active edition nodes</option>
                    <option value="no" @selected($selectedUsageFilter === 'no')>No usage</option>
                </select>
            </label>
        </form>
        <div class="result-list stack-top media-admin-scroll-panel">
            @forelse ($mediaAssets as $media)
                <article class="result-card media-library-card">
                    @if ($media->previewUrl() && $media->previewType())
                        <div class="media-preview-frame">
                            @if ($media->previewType() === 'video')
                                <video
                                    src="{{ $media->previewUrl() }}"
                                    class="media-preview-thumb media-preview-video"
                                    preload="metadata"
                                    controls
                                    muted
                                    playsinline
                                ></video>
                            @else
                                <img
                                    src="{{ $media->previewUrl() }}"
                                    alt="{{ $media->adminLabel() }}"
                                    class="media-preview-thumb"
                                    loading="lazy"
                                >
                            @endif
                        </div>
                    @endif
                    <div class="media-library-copy">
                        <p class="eyebrow">{{ ucfirst($media->asset_type) }}</p>
                        <h3><a class="result-link" href="{{ route('admin.media.edit', ['media' => $media]) }}">{{ $media->adminLabel() }}</a></h3>
                        <p class="law-meta">{{ $media->adminNodeUsageSummary() }}</p>
                        @if ($media->activeEditionUsageBadgeLabel())
                            <p class="stack-top">
                                <span class="status-badge {{ $media->activeEditionUsageBadgeClass() }}">{{ $media->activeEditionUsageBadgeLabel() }}</span>
                            </p>
                        @endif
                        @if ($media->adminDocumentPageUsageSummary())
                            <p class="law-meta">{{ $media->adminDocumentPageUsageSummary() }}</p>
                        @endif
                        @if ($media->adminTimestampSummary())
                            <p class="law-meta">{{ $media->adminTimestampSummary() }}</p>
                        @endif
                        <p class="law-meta media-source">{{ $media->adminSource() ?: 'No source' }}</p>
                    </div>
                </article>
            @empty
                <p class="empty-state">No media yet.</p>
            @endforelse
        </div>
    </section>
@endsection
