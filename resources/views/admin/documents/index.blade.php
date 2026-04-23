@extends('layouts.app')

@section('title', 'Admin | Documents')

@section('content')
    <a class="back-link" href="{{ route('admin.home') }}">Back to admin home</a>

    @include('admin.partials.edition-switcher', ['editions' => $editions, 'selectedEdition' => $selectedEdition, 'editionSwitcherTarget' => 'documents'])

    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Manage documents</h1>
        <p>Single documents and small collections for {{ $selectedEdition->name }}.</p>
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

    <details class="card collapse-card">
        <summary class="collapse-summary">
            <h2>Create document</h2>
        </summary>
        <div class="collapse-body">
            <form action="{{ route('admin.documents.store', ['edition' => $selectedEdition]) }}" method="post" class="stack-form">
                @csrf
                <label>
                    <div class="law-meta">Title (ID)</div>
                    <input type="text" name="title_id" value="{{ old('title_id') }}" data-document-title-id-input>
                </label>
                <label>
                    <div class="law-meta">Title (EN)</div>
                    <input type="text" name="title_en" value="{{ old('title_en') }}">
                </label>
                <label>
                    <div class="law-meta">Slug</div>
                    <input type="text" name="slug" value="{{ old('slug') }}" placeholder="Leave blank to generate from ID title" data-document-slug-input>
                    <div class="nav-meta">Result: <span data-document-slug-preview>-</span></div>
                </label>
                <label>
                    <div class="law-meta">Type</div>
                    <select name="type">
                        <option value="single" @selected(old('type', 'single') === 'single')>Single</option>
                        <option value="collection" @selected(old('type') === 'collection')>Collection</option>
                    </select>
                </label>
                <label>
                    <div class="law-meta">Sort order</div>
                    <input type="number" min="1" name="sort_order" value="{{ old('sort_order', 1) }}">
                </label>
                <label>
                    <div class="law-meta">Status</div>
                    <select name="status">
                        <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                        <option value="published" @selected(old('status') === 'published')>Published</option>
                    </select>
                </label>
                <button type="submit">Create document</button>
            </form>
        </div>
    </details>

    <section class="card">
        <h2>Existing documents</h2>
        <div class="result-list stack-top">
            @forelse ($documents as $document)
                <article class="result-card">
                    <h3><a class="result-link" href="{{ route('admin.documents.edit', ['edition' => $selectedEdition, 'document' => $document]) }}">{{ $document->displayTitle('id') }}</a></h3>
                    <p class="law-meta">Slug: {{ $document->slug }} | Type: {{ $document->type }} | Status: {{ $document->status }} | Pages: {{ $document->pages->count() }}</p>
                </article>
            @empty
                <p class="empty-state">No documents yet.</p>
            @endforelse
        </div>
    </section>
@endsection
