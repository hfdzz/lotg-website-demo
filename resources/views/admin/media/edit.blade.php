@extends('layouts.app')

@section('title', 'Admin | Edit Media')

@section('content')
    <a class="back-link" href="{{ route('admin.media.index') }}">Back to media library</a>

    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Edit media</h1>
        <p>Shared media changes apply everywhere this asset is attached.</p>
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

    <section class="card media-summary-card">
        <h2>{{ ucfirst($media->asset_type) }} summary</h2>
        @if ($media->previewUrl() && $media->previewType())
            <div class="media-preview-frame media-preview-frame-large">
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
        @php
            $usedCount = (int) $media->content_nodes_count + (int) $media->document_pages_count;
        @endphp
        <p class="law-meta">Usage: {{ $usedCount }} {{ \Illuminate\Support\Str::plural('place', $usedCount) }}</p>
        <p class="law-meta media-source">{{ $media->adminSource() ?: 'No source' }}</p>
    </section>

    <form action="{{ route('admin.media.update', ['media' => $media]) }}" method="post" enctype="multipart/form-data" class="stack-form">
        @csrf
        @method('patch')

        <div class="card stack-form">
            @include('admin.partials.media-fields', ['media' => $media])
        </div>

        <button type="submit">Save media</button>
    </form>

    <section class="card stack-form stack-top">
        <h2>Where this media is used</h2>
        @if ($media->contentNodes->isNotEmpty() || $media->documentPages->isNotEmpty())
            <div class="stack-top">
                @foreach ($media->contentNodes as $node)
                    @php
                        $nodeTitle = $node->translationFor(\App\Support\LotgLanguage::default())?->title ?: ucfirst(str_replace('_', ' ', $node->node_type));
                    @endphp
                    <article class="result-card">
                        <h3>
                            <a class="result-link" href="{{ route('admin.nodes.edit', ['edition' => $node->law->edition, 'law' => $node->law, 'node' => $node]) }}">
                                {{ $nodeTitle }}
                            </a>
                        </h3>
                        <p class="law-meta">Law {{ $node->law->law_number }}: {{ $node->law->displayTitle('id') }}</p>
                        <p class="law-meta">Node #{{ $node->id }} · {{ $node->node_type }} · Edition: {{ $node->law->edition?->name }}</p>
                    </article>
                @endforeach
                @foreach ($media->documentPages as $page)
                    <article class="result-card">
                        <h3>
                            <a class="result-link" href="{{ route('admin.documents.edit', ['edition' => $page->document->edition, 'document' => $page->document]) }}">
                                {{ $page->displayTitle('id') }}
                            </a>
                        </h3>
                        <p class="law-meta">Document: {{ $page->document->displayTitle('id') }}</p>
                        <p class="law-meta">Page #{{ $page->id }} · Key: {{ $page->pivot->media_key }} · Edition: {{ $page->document->edition?->name }}</p>
                    </article>
                @endforeach
            </div>
        @else
            <p class="empty-state">This media is not attached anywhere right now.</p>
        @endif
    </section>

    <section class="card stack-top">
        <h2>Delete media</h2>
        @if ($usedCount > 0)
            <p class="law-meta">This media cannot be deleted while it is still attached to nodes or document pages.</p>
        @else
            <form action="{{ route('admin.media.destroy', ['media' => $media]) }}" method="post" class="stack-form" data-confirm-message="Delete this media asset? This cannot be undone.">
                @csrf
                @method('delete')
                <button type="submit" class="button-danger">Delete media</button>
            </form>
        @endif
    </section>
@endsection
