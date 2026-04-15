@extends('layouts.app')

@section('title', 'Admin | Edit Document')

@section('content')
    <a class="back-link" href="{{ route('admin.documents.index', ['edition' => $selectedEdition]) }}">Back to documents</a>

    @include('admin.partials.edition-switcher', ['editions' => $editions, 'selectedEdition' => $selectedEdition, 'editionSwitcherTarget' => 'documents'])

    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Edit document</h1>
        <p>Manage the document record and its pages for {{ $selectedEdition->name }}.</p>
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

    <form action="{{ route('admin.documents.update', ['edition' => $selectedEdition, 'document' => $document]) }}" method="post" class="stack-form">
        @csrf
        @method('patch')

        <div class="card stack-form">
            <label>
                <div class="law-meta">Title (ID)</div>
                <input type="text" name="title_id" value="{{ old('title_id', $document->translationFor('id')?->title ?: $document->title) }}">
            </label>
            <label>
                <div class="law-meta">Title (EN)</div>
                <input type="text" name="title_en" value="{{ old('title_en', $document->translationFor('en')?->title) }}">
            </label>
            <label>
                <div class="law-meta">Slug</div>
                <input type="text" name="slug" value="{{ old('slug', $document->slug) }}">
            </label>
            <label>
                <div class="law-meta">Type</div>
                <select name="type" data-document-type-select>
                    <option value="single" @selected(old('type', $document->type) === 'single')>Single</option>
                    <option value="collection" @selected(old('type', $document->type) === 'collection')>Collection</option>
                </select>
            </label>
            <label>
                <div class="law-meta">Sort order</div>
                <input type="number" min="1" name="sort_order" value="{{ old('sort_order', $document->sort_order) }}">
            </label>
            <label>
                <div class="law-meta">Status</div>
                <select name="status">
                    <option value="draft" @selected(old('status', $document->status) === 'draft')>Draft</option>
                    <option value="published" @selected(old('status', $document->status) === 'published')>Published</option>
                </select>
            </label>
        </div>

        <div class="stack-form" data-document-pages-editor>
            @php
                $oldInput = session()->getOldInput();
                $hasOldPageState = is_array($oldInput)
                    && (array_key_exists('pages', $oldInput) || array_key_exists('remove_page_ids', $oldInput));
                $removedPageIds = collect(old('remove_page_ids', []))
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->values();
                $pageRows = collect($hasOldPageState ? old('pages', []) : [])
                    ->when(! $hasOldPageState, function () use ($document) {
                        return $document->pages->map(fn ($page) => [
                            'id' => $page->id,
                            'slug' => $page->slug,
                            'title_id' => $page->translationFor('id')?->title ?: $page->title,
                            'title_en' => $page->translationFor('en')?->title,
                            'body_html_id' => $page->translationFor('id')?->body_html ?: $page->body_html,
                            'body_html_en' => $page->translationFor('en')?->body_html,
                            'sort_order' => $page->sort_order,
                            'status' => $page->status,
                        ]);
                    })
                    ->values();
                if ($pageRows->isEmpty() && ! $hasOldPageState) {
                    $pageRows = collect([[
                        'id' => null,
                        'slug' => '',
                        'title_id' => '',
                        'title_en' => '',
                        'body_html_id' => '',
                        'body_html_en' => '',
                        'sort_order' => 1,
                        'status' => $document->status,
                    ]]);
                }
            @endphp

            <div data-document-removed-pages hidden>
                @foreach ($removedPageIds as $removedPageId)
                    <input type="hidden" name="remove_page_ids[]" value="{{ $removedPageId }}">
                @endforeach
            </div>

            @foreach ($pageRows as $index => $pageRow)
                <div class="card stack-form document-page-card" data-document-page-item @if (!empty($pageRow['id'])) data-document-page-id="{{ $pageRow['id'] }}" @endif>
                    <div class="video-item-header">
                        <h2>Page <span data-document-page-number>{{ $index + 1 }}</span></h2>
                        <button type="button" class="button-danger" data-document-page-remove data-confirm-message="Remove this page? Unsaved changes in this page will be lost.">Remove page</button>
                    </div>
                    <input type="hidden" name="pages[{{ $index }}][id]" value="{{ $pageRow['id'] ?? '' }}">
                    <label>
                        <div class="law-meta">Page slug</div>
                        <input type="text" name="pages[{{ $index }}][slug]" value="{{ $pageRow['slug'] ?? '' }}">
                    </label>
                    <label>
                        <div class="law-meta">Page title (ID)</div>
                        <input type="text" name="pages[{{ $index }}][title_id]" value="{{ $pageRow['title_id'] ?? '' }}">
                    </label>
                    <label>
                        <div class="law-meta">Page title (EN)</div>
                        <input type="text" name="pages[{{ $index }}][title_en]" value="{{ $pageRow['title_en'] ?? '' }}">
                    </label>
                    <label>
                        <div class="law-meta">Body HTML (ID)</div>
                        <textarea name="pages[{{ $index }}][body_html_id]" rows="10">{{ $pageRow['body_html_id'] ?? '' }}</textarea>
                    </label>
                    <label>
                        <div class="law-meta">Body HTML (EN)</div>
                        <textarea name="pages[{{ $index }}][body_html_en]" rows="10">{{ $pageRow['body_html_en'] ?? '' }}</textarea>
                    </label>
                    <label data-document-collection-only>
                        <div class="law-meta">Sort order</div>
                        <input type="number" min="1" name="pages[{{ $index }}][sort_order]" value="{{ $pageRow['sort_order'] ?? ($index + 1) }}">
                    </label>
                    <label>
                        <div class="law-meta">Page status</div>
                        <select name="pages[{{ $index }}][status]">
                            <option value="draft" @selected(($pageRow['status'] ?? $document->status) === 'draft')>Draft</option>
                            <option value="published" @selected(($pageRow['status'] ?? $document->status) === 'published')>Published</option>
                        </select>
                    </label>
                </div>
            @endforeach
        </div>

        <button type="button" data-document-page-add>Add page</button>
        <button type="submit">Save document</button>
    </form>

    @can('delete', $document)
        <form action="{{ route('admin.documents.destroy', ['edition' => $selectedEdition, 'document' => $document]) }}" method="post" class="stack-form stack-top" data-confirm-message="Delete this document? This will also remove all of its pages and translations.">
            @csrf
            @method('delete')
            <button type="submit" class="button-danger">Delete document</button>
        </form>
    @endcan
@endsection
