@extends('layouts.app')

@section('title', 'Admin | Edit Document')

@section('content')
    <a class="back-link" href="{{ route('admin.documents.index') }}">Back to documents</a>

    <section class="hero">
        <p class="eyebrow">Admin</p>
        <h1>Edit document</h1>
        <p>Manage the document record and its pages.</p>
    </section>

    @if (session('status'))
        <div class="card surface-note flash-message flash-message-success">
            <strong>{{ session('status') }}</strong>
        </div>
    @endif

    <form action="{{ route('admin.documents.update', ['document' => $document]) }}" method="post" class="stack-form">
        @csrf
        @method('patch')

        <div class="card stack-form">
            <label>
                <div class="law-meta">Title</div>
                <input type="text" name="title" value="{{ old('title', $document->title) }}">
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
                $pageRows = collect(old('pages'))
                    ->whenEmpty(function () use ($document) {
                        return $document->pages->map(fn ($page) => [
                            'id' => $page->id,
                            'slug' => $page->slug,
                            'title' => $page->title,
                            'body_html' => $page->body_html,
                            'sort_order' => $page->sort_order,
                            'status' => $page->status,
                        ]);
                    })
                    ->values();
                if ($pageRows->isEmpty()) {
                    $pageRows = collect([[
                        'id' => null,
                        'slug' => '',
                        'title' => '',
                        'body_html' => '',
                        'sort_order' => 1,
                        'status' => $document->status,
                    ]]);
                }
            @endphp

            @foreach ($pageRows as $index => $pageRow)
                <div class="card stack-form document-page-card" data-document-page-item>
                    <div class="video-item-header">
                        <h2>Page <span data-document-page-number>{{ $index + 1 }}</span></h2>
                        @if (!empty($pageRow['id']))
                            <label class="law-meta">
                                <input type="checkbox" name="remove_page_ids[]" value="{{ $pageRow['id'] }}">
                                Remove page
                            </label>
                        @endif
                    </div>
                    <input type="hidden" name="pages[{{ $index }}][id]" value="{{ $pageRow['id'] ?? '' }}">
                    <label>
                        <div class="law-meta">Page slug</div>
                        <input type="text" name="pages[{{ $index }}][slug]" value="{{ $pageRow['slug'] ?? '' }}">
                    </label>
                    <label>
                        <div class="law-meta">Page title</div>
                        <input type="text" name="pages[{{ $index }}][title]" value="{{ $pageRow['title'] ?? '' }}">
                    </label>
                    <label>
                        <div class="law-meta">Body HTML</div>
                        <textarea name="pages[{{ $index }}][body_html]" rows="10">{{ $pageRow['body_html'] ?? '' }}</textarea>
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
@endsection
