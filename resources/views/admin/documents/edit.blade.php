@extends('layouts.app')

@section('title', 'Admin | Edit Document')

@section('content')
    @php
        $availableImageAssets = $availableImageAssets ?? collect();
        $imagePreviewMap = $availableImageAssets
            ->mapWithKeys(fn ($asset) => [
                (string) $asset->id => [
                    'url' => $asset->thumbnailUrl(),
                    'label' => $asset->adminLabel(),
                ],
            ])
            ->all();
    @endphp

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

    <form action="{{ route('admin.documents.update', ['edition' => $selectedEdition, 'document' => $document]) }}" method="post" enctype="multipart/form-data" class="stack-form">
        @csrf
        @method('patch')

        <div class="card stack-form">
            <label>
                <div class="law-meta">Title (ID)</div>
                <input type="text" name="title_id" value="{{ old('title_id', $document->translationFor('id')?->title ?: $document->title) }}" data-document-title-id-input>
            </label>
            <label>
                <div class="law-meta">Title (EN)</div>
                <input type="text" name="title_en" value="{{ old('title_en', $document->translationFor('en')?->title) }}">
            </label>
            <label>
                <div class="law-meta">Slug</div>
                <input type="text" name="slug" value="{{ old('slug', $document->slug) }}" placeholder="Leave blank to generate from ID title" data-document-slug-input>
                <div class="nav-meta">Result: <span data-document-slug-preview>{{ $document->slug }}</span></div>
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
                            'media' => $page->mediaAssets->map(fn ($asset) => [
                                'pivot_id' => $asset->pivot->id,
                                'media_key' => $asset->pivot->media_key,
                                'existing_media_asset_id' => $asset->id,
                                'caption' => $asset->caption,
                                'credit' => $asset->credit,
                                'sort_order' => $asset->pivot->sort_order,
                                'remove' => 0,
                            ])->values()->all(),
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
                        'media' => [],
                    ]]);
                }
            @endphp

            <div data-document-removed-pages hidden>
                @foreach ($removedPageIds as $removedPageId)
                    <input type="hidden" name="remove_page_ids[]" value="{{ $removedPageId }}">
                @endforeach
            </div>

            @foreach ($pageRows as $index => $pageRow)
                @php
                    $pageTitle = trim((string) (($pageRow['title_id'] ?? '') ?: ($pageRow['title_en'] ?? '')));
                    $visibleMediaCount = collect($pageRow['media'] ?? [])
                        ->reject(fn ($mediaRow) => (bool) ($mediaRow['remove'] ?? false))
                        ->count();
                @endphp
                <details class="card document-page-card document-collapse-card" data-document-page-item @if (!empty($pageRow['id'])) data-document-page-id="{{ $pageRow['id'] }}" @endif>
                    <summary class="document-collapse-summary">
                        <span class="document-summary-main">
                            <span class="document-summary-title">Page <span data-document-page-number>{{ $index + 1 }}</span>: <span data-document-page-summary-title>{{ $pageTitle ?: 'Untitled page' }}</span></span>
                            <span class="document-summary-meta">
                                Slug: <span data-document-page-summary-slug>{{ $pageRow['slug'] ?? '-' }}</span>
                                - <span data-document-page-summary-media-count>{{ $visibleMediaCount }}</span> image<span data-document-page-summary-media-suffix>{{ $visibleMediaCount === 1 ? '' : 's' }}</span>
                                - <span data-document-page-summary-status>{{ ucfirst($pageRow['status'] ?? $document->status) }}</span>
                            </span>
                        </span>
                    </summary>
                    <div class="collapse-body stack-form">
                    <div class="video-item-header">
                        <h2>Page <span data-document-page-number>{{ $index + 1 }}</span></h2>
                        <button type="button" class="button-danger" data-document-page-remove data-confirm-message="Remove this page? Unsaved changes in this page will be lost.">Remove page</button>
                    </div>
                    <input type="hidden" name="pages[{{ $index }}][id]" value="{{ $pageRow['id'] ?? '' }}">
                    <label>
                        <div class="law-meta">Page slug</div>
                        <input type="text" name="pages[{{ $index }}][slug]" value="{{ $pageRow['slug'] ?? '' }}" placeholder="Leave blank to generate from page ID title" data-document-page-slug-input>
                        <div class="nav-meta">Result: <span data-document-page-slug-preview>{{ $pageRow['slug'] ?? '-' }}</span></div>
                    </label>
                    <label>
                        <div class="law-meta">Page title (ID)</div>
                        <input type="text" name="pages[{{ $index }}][title_id]" value="{{ $pageRow['title_id'] ?? '' }}" data-document-page-title-id-input>
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
                        <select name="pages[{{ $index }}][status]" data-document-page-status-select>
                            <option value="draft" @selected(($pageRow['status'] ?? $document->status) === 'draft')>Draft</option>
                            <option value="published" @selected(($pageRow['status'] ?? $document->status) === 'published')>Published</option>
                        </select>
                    </label>

                    @php
                        $mediaRows = collect($pageRow['media'] ?? [])->values();
                    @endphp

                    <div class="document-inline-media-editor" data-document-media-editor data-document-media-preview-map='@json($imagePreviewMap)'>
                        <div class="video-item-header">
                            <div>
                                <h3>Inline media</h3>
                                <p class="nav-meta">Attach image assets to this page, then place them in the body with placeholders like <code>@{{media:example-key}}</code>.</p>
                            </div>
                            <button type="button" data-document-media-add>Add image</button>
                        </div>

                        <div class="stack-form" data-document-media-list>
                            @foreach ($mediaRows as $mediaIndex => $mediaRow)
                                @php
                                    $selectedMediaId = $mediaRow['existing_media_asset_id'] ?? '';
                                    $mediaKey = $mediaRow['media_key'] ?? '';
                                    $isRemoved = (bool) ($mediaRow['remove'] ?? false);
                                    $selectedMedia = $selectedMediaId ? $availableImageAssets->firstWhere('id', (int) $selectedMediaId) : null;
                                    $mediaTitle = $mediaKey ?: ($mediaRow['caption'] ?? '') ?: ($selectedMedia?->adminLabel() ?? 'Untitled image');
                                @endphp
                                <details class="card document-media-card document-collapse-card" data-document-media-item @if (! empty($mediaRow['pivot_id'])) data-document-media-pivot-id="{{ $mediaRow['pivot_id'] }}" @endif @if($isRemoved) hidden @endif>
                                    <summary class="document-collapse-summary document-media-summary">
                                        <span class="document-summary-main">
                                            <span class="document-summary-title">Image <span data-document-media-number>{{ $mediaIndex + 1 }}</span>: <span data-document-media-summary-title>{{ $mediaTitle }}</span></span>
                                            <span class="document-summary-meta">
                                                Key: <span data-document-media-summary-key>{{ $mediaKey ?: '-' }}</span>
                                                - <span data-document-media-summary-caption>{{ ($mediaRow['caption'] ?? '') ?: ($selectedMedia?->caption ?: 'No caption') }}</span>
                                            </span>
                                        </span>
                                    </summary>
                                    <div class="collapse-body stack-form">
                                    <div class="video-item-header">
                                        <h4>Image <span data-document-media-number>{{ $mediaIndex + 1 }}</span></h4>
                                        <button type="button" class="button-danger" data-document-media-remove data-confirm-message="Remove this inline image from the page?">Remove image</button>
                                    </div>
                                    <input type="hidden" name="pages[{{ $index }}][media][{{ $mediaIndex }}][pivot_id]" value="{{ $mediaRow['pivot_id'] ?? '' }}">
                                    <input type="hidden" name="pages[{{ $index }}][media][{{ $mediaIndex }}][remove]" value="{{ $isRemoved ? 1 : 0 }}" data-document-media-remove-input>
                                    <label>
                                        <div class="law-meta">Media key</div>
                                        <input type="text" name="pages[{{ $index }}][media][{{ $mediaIndex }}][media_key]" value="{{ $mediaKey }}" placeholder="example-key" data-document-media-key>
                                    </label>
                                    @php
                                        $placeholderKey = $mediaKey ?: 'example-key';
                                        $placeholder = '{'.'{media:'.$placeholderKey.'}'.'}';
                                    @endphp
                                    <p class="nav-meta">
                                        Placeholder: <code data-document-media-placeholder>{{ $placeholder }}</code>
                                    </p>
                                    <label>
                                        <div class="law-meta">Use existing image</div>
                                        <select name="pages[{{ $index }}][media][{{ $mediaIndex }}][existing_media_asset_id]" data-document-media-existing-select>
                                            <option value="">Upload a new image instead</option>
                                            @foreach ($availableImageAssets as $availableImageAsset)
                                                @php
                                                    $usedCount = (int) ($availableImageAsset->content_nodes_count ?? 0) + (int) ($availableImageAsset->document_pages_count ?? 0);
                                                @endphp
                                                <option value="{{ $availableImageAsset->id }}" @selected((string) $selectedMediaId === (string) $availableImageAsset->id)>
                                                    #{{ $availableImageAsset->id }} - {{ $availableImageAsset->adminLabel() }} - used {{ $usedCount }}x
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <div class="media-selection-preview" data-document-media-selection-preview hidden></div>
                                    <label>
                                        <div class="law-meta">Upload image</div>
                                        <input type="file" name="pages[{{ $index }}][media][{{ $mediaIndex }}][image_file]" accept=".jpg,.jpeg,.png,.gif,.bmp,.webp,.avif,.svg,image/jpeg,image/png,image/gif,image/bmp,image/webp,image/avif,image/svg+xml" data-document-media-new-field>
                                    </label>
                                    <label>
                                        <div class="law-meta">Caption</div>
                                        <input type="text" name="pages[{{ $index }}][media][{{ $mediaIndex }}][caption]" value="{{ $mediaRow['caption'] ?? '' }}" data-document-media-new-field data-document-media-caption-input>
                                    </label>
                                    <label>
                                        <div class="law-meta">Credit / attribution</div>
                                        <input type="text" name="pages[{{ $index }}][media][{{ $mediaIndex }}][credit]" value="{{ $mediaRow['credit'] ?? '' }}" data-document-media-new-field>
                                    </label>
                                    <label>
                                        <div class="law-meta">Sort order</div>
                                        <input type="number" min="1" name="pages[{{ $index }}][media][{{ $mediaIndex }}][sort_order]" value="{{ $mediaRow['sort_order'] ?? ($mediaIndex + 1) }}">
                                    </label>
                                    </div>
                                </details>
                            @endforeach
                        </div>

                        <template data-document-media-template>
                            <summary class="document-collapse-summary document-media-summary">
                                <span class="document-summary-main">
                                    <span class="document-summary-title">Image <span data-document-media-number>__NUMBER__</span>: <span data-document-media-summary-title>Untitled image</span></span>
                                    <span class="document-summary-meta">
                                        Key: <span data-document-media-summary-key>-</span>
                                        - <span data-document-media-summary-caption>No caption</span>
                                    </span>
                                </span>
                            </summary>
                            <div class="collapse-body stack-form">
                            <div class="video-item-header">
                                <h4>Image <span data-document-media-number>__NUMBER__</span></h4>
                                <button type="button" class="button-danger" data-document-media-remove data-confirm-message="Remove this inline image from the page?">Remove image</button>
                            </div>
                            <input type="hidden" name="pages[__PAGE_INDEX__][media][__MEDIA_INDEX__][pivot_id]" value="">
                            <input type="hidden" name="pages[__PAGE_INDEX__][media][__MEDIA_INDEX__][remove]" value="0" data-document-media-remove-input>
                            <label>
                                <div class="law-meta">Media key</div>
                                <input type="text" name="pages[__PAGE_INDEX__][media][__MEDIA_INDEX__][media_key]" value="" placeholder="example-key" data-document-media-key>
                            </label>
                            <p class="nav-meta">Placeholder: <code data-document-media-placeholder>@{{media:example-key}}</code></p>
                            <label>
                                <div class="law-meta">Use existing image</div>
                                <select name="pages[__PAGE_INDEX__][media][__MEDIA_INDEX__][existing_media_asset_id]" data-document-media-existing-select>
                                    <option value="">Upload a new image instead</option>
                                    @foreach ($availableImageAssets as $availableImageAsset)
                                        @php
                                            $usedCount = (int) ($availableImageAsset->content_nodes_count ?? 0) + (int) ($availableImageAsset->document_pages_count ?? 0);
                                        @endphp
                                        <option value="{{ $availableImageAsset->id }}">#{{ $availableImageAsset->id }} - {{ $availableImageAsset->adminLabel() }} - used {{ $usedCount }}x</option>
                                    @endforeach
                                </select>
                            </label>
                            <div class="media-selection-preview" data-document-media-selection-preview hidden></div>
                            <label>
                                <div class="law-meta">Upload image</div>
                                <input type="file" name="pages[__PAGE_INDEX__][media][__MEDIA_INDEX__][image_file]" accept=".jpg,.jpeg,.png,.gif,.bmp,.webp,.avif,.svg,image/jpeg,image/png,image/gif,image/bmp,image/webp,image/avif,image/svg+xml" data-document-media-new-field>
                            </label>
                            <label>
                                <div class="law-meta">Caption</div>
                                <input type="text" name="pages[__PAGE_INDEX__][media][__MEDIA_INDEX__][caption]" value="" data-document-media-new-field data-document-media-caption-input>
                            </label>
                            <label>
                                <div class="law-meta">Credit / attribution</div>
                                <input type="text" name="pages[__PAGE_INDEX__][media][__MEDIA_INDEX__][credit]" value="" data-document-media-new-field>
                            </label>
                            <label>
                                <div class="law-meta">Sort order</div>
                                <input type="number" min="1" name="pages[__PAGE_INDEX__][media][__MEDIA_INDEX__][sort_order]" value="__NUMBER__">
                            </label>
                            </div>
                        </template>
                    </div>
                    </div>
                </details>
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
