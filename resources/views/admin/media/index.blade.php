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

    <details class="card collapse-card" @if($errors->any()) open @endif>
        <summary class="collapse-summary">
            <h2>Add media</h2>
        </summary>
        <div class="collapse-body">
            <form action="{{ route('admin.media.store') }}" method="post" enctype="multipart/form-data" class="stack-form">
                @csrf
                @include('admin.partials.media-fields', ['media' => null])
                <button type="submit">Create media</button>
            </form>
        </div>
    </details>

    <section class="card">
        <h2>Existing media</h2>
        <div class="result-list stack-top">
            @forelse ($mediaAssets as $media)
                <article class="result-card media-library-card">
                    @if ($media->thumbnailUrl())
                        <div class="media-preview-frame">
                            <img
                                src="{{ $media->thumbnailUrl() }}"
                                alt="{{ $media->adminLabel() }}"
                                class="media-preview-thumb"
                                loading="lazy"
                            >
                        </div>
                    @endif
                    <div class="media-library-copy">
                        <p class="eyebrow">{{ ucfirst($media->asset_type) }}</p>
                        <h3><a class="result-link" href="{{ route('admin.media.edit', ['media' => $media]) }}">{{ $media->adminLabel() }}</a></h3>
                        @php
                            $usedCount = (int) $media->content_nodes_count + (int) $media->document_pages_count;
                        @endphp
                        <p class="law-meta">Used in {{ $usedCount }} {{ \Illuminate\Support\Str::plural('place', $usedCount) }}</p>
                        <p class="law-meta media-source">{{ $media->adminSource() ?: 'No source' }}</p>
                    </div>
                </article>
            @empty
                <p class="empty-state">No media yet.</p>
            @endforelse
        </div>
    </section>
@endsection
