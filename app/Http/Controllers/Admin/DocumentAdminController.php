<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\Edition;
use App\Models\MediaAsset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentAdminController extends Controller
{
    public function home(): View|RedirectResponse
    {
        $this->authorize('viewAny', Document::class);

        $activeEdition = Edition::current();

        if ($activeEdition) {
            return redirect()->route('admin.documents.index', ['edition' => $activeEdition]);
        }

        $fallbackEdition = Edition::query()->orderByDesc('year_start')->orderByDesc('year_end')->first();

        if ($fallbackEdition) {
            return redirect()->route('admin.documents.index', ['edition' => $fallbackEdition]);
        }

        return redirect()->route('admin.editions.index');
    }

    public function index(Edition $edition): View
    {
        $this->authorize('viewAny', Document::class);

        return view('admin.documents.index', [
            'documents' => Document::query()
                ->forEdition($edition->id)
                ->with(['translations', 'pages.translations'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'selectedEdition' => $edition,
            'editions' => Edition::query()->orderByDesc('year_start')->orderByDesc('year_end')->get(),
        ]);
    }

    public function store(Request $request, Edition $edition): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $validated = $request->validate([
            'title_id' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:single,collection'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $baseTitle = $validated['title_id'] ?: ($validated['title_en'] ?? '');
        $slug = $this->documentSlugFromInput($validated['slug'] ?? null, $baseTitle);

        if ($this->slugExistsInEdition($edition, $slug)) {
            return back()
                ->withErrors(['slug' => 'The slug has already been taken in this edition.'])
                ->withInput();
        }

        $document = DB::transaction(function () use ($edition, $validated, $baseTitle, $slug) {
            $document = Document::create([
                'edition_id' => $edition->id,
                'title' => $baseTitle,
                'slug' => $slug,
                'type' => $validated['type'],
                'sort_order' => $validated['sort_order'],
                'status' => $validated['status'],
            ]);

            $this->syncDocumentTranslations($document, $validated);

            if ($document->type === 'single') {
                $page = $document->pages()->create([
                    'slug' => 'overview',
                    'title' => $document->displayTitle('id'),
                    'body_html' => null,
                    'sort_order' => 1,
                    'status' => $document->status,
                ]);

                $this->syncDocumentPageTranslations($page, [
                    'title_id' => $validated['title_id'],
                    'title_en' => $validated['title_en'] ?? null,
                    'body_html_id' => null,
                    'body_html_en' => null,
                ]);
            }

            return $document;
        });

        return redirect()->route('admin.documents.edit', ['edition' => $edition, 'document' => $document])->with('status', 'Document created.');
    }

    public function edit(Edition $edition, Document $document): View
    {
        $this->authorize('update', $document);
        abort_unless((int) $document->edition_id === (int) $edition->id, 404);
        $document->load(['translations', 'pages.translations', 'pages.mediaAssets']);

        return view('admin.documents.edit', [
            'document' => $document,
            'selectedEdition' => $edition,
            'editions' => Edition::query()->orderByDesc('year_start')->orderByDesc('year_end')->get(),
            'availableImageAssets' => $this->availableImageAssets(),
        ]);
    }

    public function update(Request $request, Edition $edition, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);
        abort_unless((int) $document->edition_id === (int) $edition->id, 404);

        $validated = $request->validate([
            'title_id' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:single,collection'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,published'],
            'pages' => ['nullable', 'array'],
            'pages.*.id' => ['nullable', 'integer'],
            'pages.*.slug' => ['nullable', 'string', 'max:255'],
            'pages.*.title_id' => ['nullable', 'string', 'max:255'],
            'pages.*.title_en' => ['nullable', 'string', 'max:255'],
            'pages.*.body_html_id' => ['nullable', 'string'],
            'pages.*.body_html_en' => ['nullable', 'string'],
            'pages.*.sort_order' => ['nullable', 'integer', 'min:1'],
            'pages.*.status' => ['nullable', 'in:draft,published'],
            'pages.*.media' => ['nullable', 'array'],
            'pages.*.media.*.pivot_id' => ['nullable', 'integer'],
            'pages.*.media.*.media_key' => ['nullable', 'string', 'max:80'],
            'pages.*.media.*.existing_media_asset_id' => [
                'nullable',
                'integer',
                'exists:media_assets,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! $value) {
                        return;
                    }

                    $asset = MediaAsset::query()->find($value);

                    if (! $asset || $asset->asset_type !== 'image' || ! $asset->is_library_item) {
                        $fail('Selected document media must be an existing reusable image asset.');
                    }
                },
            ],
            'pages.*.media.*.caption' => ['nullable', 'string'],
            'pages.*.media.*.credit' => ['nullable', 'string', 'max:255'],
            'pages.*.media.*.sort_order' => ['nullable', 'integer', 'min:1'],
            'pages.*.media.*.remove' => ['nullable', 'boolean'],
            'pages.*.media.*.image_file' => [
                'nullable',
                'file',
                'mimetypes:image/jpeg,image/png,image/gif,image/bmp,image/webp,image/avif,image/svg+xml',
                'max:5120',
            ],
            'remove_page_ids' => ['nullable', 'array'],
            'remove_page_ids.*' => ['integer'],
        ]);

        if ($mediaKeyErrors = $this->documentMediaKeyErrors($request)) {
            return back()
                ->withErrors($mediaKeyErrors)
                ->withInput();
        }

        $baseTitle = $validated['title_id'] ?: ($validated['title_en'] ?? '');
        $slug = $this->documentSlugFromInput($validated['slug'] ?? null, $baseTitle);

        if ($this->slugExistsInEdition($edition, $slug, $document->id)) {
            return back()
                ->withErrors(['slug' => 'The slug has already been taken in this edition.'])
                ->withInput();
        }

        DB::transaction(function () use ($document, $validated, $request, $baseTitle, $slug) {
            $document->update([
                'title' => $baseTitle,
                'slug' => $slug,
                'type' => $validated['type'],
                'sort_order' => $validated['sort_order'],
                'status' => $validated['status'],
            ]);

            $this->syncDocumentTranslations($document, $validated);

            $removeIds = collect($request->input('remove_page_ids', []))->map(fn ($id) => (int) $id)->all();
            if ($removeIds) {
                $document->pages()->whereIn('id', $removeIds)->delete();
                $this->normalizePageSortOrders($document);
            }

            foreach ($request->input('pages', []) as $index => $pageData) {
                $this->syncDocumentPageFromRequest($request, $document, $validated, $pageData, $index);
            }

            if ($document->type === 'single' && $document->pages()->count() === 0) {
                $page = $document->pages()->create([
                    'slug' => 'overview',
                    'title' => $document->displayTitle('id'),
                    'body_html' => null,
                    'sort_order' => 1,
                    'status' => $document->status,
                ]);

                $this->syncDocumentPageTranslations($page, [
                    'title_id' => $document->displayTitle('id'),
                    'title_en' => $document->displayTitle('en'),
                    'body_html_id' => null,
                    'body_html_en' => null,
                ]);
            }

            $this->normalizePageSortOrders($document);
        });

        return redirect()->route('admin.documents.edit', ['edition' => $edition, 'document' => $document])->with('status', 'Document updated.');
    }

    public function destroy(Edition $edition, Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);
        abort_unless((int) $document->edition_id === (int) $edition->id, 404);

        $document->delete();

        return redirect()
            ->route('admin.documents.index', ['edition' => $edition])
            ->with('status', 'Document deleted.');
    }

    protected function syncDocumentTranslations(Document $document, array $validated): void
    {
        foreach (['id', 'en'] as $languageCode) {
            $title = $validated['title_'.$languageCode] ?? null;

            if ($languageCode === 'id' || filled($title)) {
                $document->translations()->updateOrCreate(
                    ['language_code' => $languageCode],
                    ['title' => $title ?: ($validated['title_id'] ?? $document->title)]
                );
            } else {
                $document->translations()->where('language_code', $languageCode)->delete();
            }
        }
    }

    protected function syncDocumentPageFromRequest(Request $request, Document $document, array $validated, array $pageData, int $index): void
    {
        $pageTitleId = trim((string) ($pageData['title_id'] ?? ''));
        $pageTitleEn = trim((string) ($pageData['title_en'] ?? ''));
        $pageSlug = trim((string) ($pageData['slug'] ?? ''));
        $pageBodyId = (string) ($pageData['body_html_id'] ?? '');
        $pageBodyEn = (string) ($pageData['body_html_en'] ?? '');
        $pageStatus = $pageData['status'] ?? $validated['status'];
        $requestedSortOrder = (int) ($pageData['sort_order'] ?? ($index + 1));

        if ($document->type === 'single') {
            $pageTitleId = $pageTitleId !== '' ? $pageTitleId : ($validated['title_id'] ?? $document->displayTitle('id'));
            $pageTitleEn = $pageTitleEn !== '' ? $pageTitleEn : ($validated['title_en'] ?? $document->displayTitle('en'));
            $pageSlug = $pageSlug !== '' ? $pageSlug : 'overview';
        }

        if (
            $pageTitleId === ''
            && $pageTitleEn === ''
            && trim($pageBodyId) === ''
            && trim($pageBodyEn) === ''
            && $pageSlug === ''
        ) {
            return;
        }

        $basePageTitle = $pageTitleId !== '' ? $pageTitleId : ($pageTitleEn !== '' ? $pageTitleEn : $document->displayTitle('id'));
        $basePageBody = trim($pageBodyId) !== '' ? $pageBodyId : (trim($pageBodyEn) !== '' ? $pageBodyEn : null);

        $payload = [
            'slug' => $this->documentPageSlugFromInput($pageSlug, $basePageTitle),
            'title' => $basePageTitle,
            'body_html' => $basePageBody,
            'status' => $pageStatus,
        ];

        if (! empty($pageData['id'])) {
            $page = $document->pages()->whereKey((int) $pageData['id'])->first();

            if (! $page) {
                return;
            }

            $page->update($payload);

            if ($requestedSortOrder !== (int) $page->sort_order) {
                $this->moveDocumentPage($document, $page, $requestedSortOrder);
            }

            $this->syncDocumentPageTranslations($page, $pageData);
            $this->syncDocumentPageMedia($request, $page, $index);

            return;
        }

        $finalSortOrder = min(max($requestedSortOrder, 1), $this->nextPageSortOrder($document));
        $this->shiftPagesForInsert($document, $finalSortOrder);

        $page = $document->pages()->create([
            ...$payload,
            'sort_order' => $finalSortOrder,
        ]);

        $this->syncDocumentPageTranslations($page, $pageData);
        $this->syncDocumentPageMedia($request, $page, $index);
    }

    protected function syncDocumentPageTranslations(DocumentPage $page, array $pageData): void
    {
        foreach (['id', 'en'] as $languageCode) {
            $title = $pageData['title_'.$languageCode] ?? null;
            $bodyHtml = $pageData['body_html_'.$languageCode] ?? null;

            if ($languageCode === 'id' || filled($title) || filled($bodyHtml)) {
                $page->translations()->updateOrCreate(
                    ['language_code' => $languageCode],
                    [
                        'title' => $title ?: ($pageData['title_id'] ?? $page->title),
                        'body_html' => filled($bodyHtml) ? $bodyHtml : null,
                    ]
                );
            } else {
                $page->translations()->where('language_code', $languageCode)->delete();
            }
        }
    }

    protected function syncDocumentPageMedia(Request $request, DocumentPage $page, int $pageIndex): void
    {
        $rows = collect($request->input("pages.$pageIndex.media", []))->values();
        $currentAssets = $page->mediaAssets()->get()->keyBy(fn (MediaAsset $asset) => (int) $asset->pivot->id);
        $syncPayload = [];
        $usedKeys = [];

        foreach ($rows as $mediaIndex => $row) {
            $pivotId = (int) ($row['pivot_id'] ?? 0);
            $isRemoved = (bool) ($row['remove'] ?? false);

            if ($isRemoved) {
                continue;
            }

            $asset = null;
            $selectedAssetId = (int) ($row['existing_media_asset_id'] ?? 0);

            if ($selectedAssetId > 0) {
                $asset = MediaAsset::query()
                    ->libraryItems()
                    ->ofAssetType('image')
                    ->find($selectedAssetId);
            } elseif ($request->hasFile("pages.$pageIndex.media.$mediaIndex.image_file")) {
                $uploadedFile = $request->file("pages.$pageIndex.media.$mediaIndex.image_file");
                $path = $uploadedFile->store('lotg-media/images', 'public');

                $asset = MediaAsset::create([
                    'asset_type' => 'image',
                    'storage_type' => 'upload',
                    'is_library_item' => true,
                    'file_path' => $path,
                    'caption' => trim((string) ($row['caption'] ?? '')) ?: $uploadedFile->getClientOriginalName(),
                    'credit' => trim((string) ($row['credit'] ?? '')) ?: null,
                ]);
            } elseif ($pivotId > 0 && $currentAssets->has($pivotId)) {
                $asset = $currentAssets->get($pivotId);
            }

            if (! $asset) {
                continue;
            }

            $mediaKey = $this->normalizeDocumentMediaKey(
                (string) ($row['media_key'] ?? ''),
                $asset,
                $mediaIndex + 1,
            );

            if (isset($usedKeys[$mediaKey])) {
                continue;
            }

            $usedKeys[$mediaKey] = true;
            $syncPayload[$asset->id] = [
                'media_key' => $mediaKey,
                'sort_order' => (int) ($row['sort_order'] ?? ($mediaIndex + 1)),
            ];
        }

        $page->mediaAssets()->sync($syncPayload);
    }

    /**
     * @return array<string, string>
     */
    protected function documentMediaKeyErrors(Request $request): array
    {
        $errors = [];

        foreach ($request->input('pages', []) as $pageIndex => $pageData) {
            $seenKeys = [];
            $seenAssetIds = [];

            foreach (($pageData['media'] ?? []) as $mediaIndex => $mediaRow) {
                if ((bool) ($mediaRow['remove'] ?? false)) {
                    continue;
                }

                $assetId = (int) ($mediaRow['existing_media_asset_id'] ?? 0);

                if ($assetId > 0) {
                    if (isset($seenAssetIds[$assetId])) {
                        $errors["pages.$pageIndex.media.$mediaIndex.existing_media_asset_id"] = 'The same image can only be attached once within the same page.';
                    }

                    $seenAssetIds[$assetId] = true;
                }

                $mediaKey = Str::slug((string) ($mediaRow['media_key'] ?? ''));

                if ($mediaKey === '') {
                    continue;
                }

                if (isset($seenKeys[$mediaKey])) {
                    $errors["pages.$pageIndex.media.$mediaIndex.media_key"] = 'Document media keys must be unique within the same page.';
                }

                $seenKeys[$mediaKey] = true;
            }
        }

        return $errors;
    }

    protected function normalizeDocumentMediaKey(string $mediaKey, MediaAsset $asset, int $fallbackIndex): string
    {
        $normalized = Str::slug($mediaKey);

        if ($normalized !== '') {
            return $normalized;
        }

        return Str::slug($asset->caption ?: $asset->adminLabel()) ?: 'media-'.$fallbackIndex;
    }

    protected function moveDocumentPage(Document $document, DocumentPage $page, int $requestedSortOrder): void
    {
        $currentSortOrder = (int) $page->sort_order;
        $this->normalizePageSortOrders($document, $page->id);

        $maxSortOrder = $this->nextPageSortOrder($document, $page->id);
        $finalSortOrder = min(max($requestedSortOrder, 1), $maxSortOrder);

        if ($finalSortOrder !== $currentSortOrder) {
            $this->shiftPagesForInsert($document, $finalSortOrder, $page->id);
        }

        $page->update(['sort_order' => $finalSortOrder]);
    }

    protected function nextPageSortOrder(Document $document, ?int $excludePageId = null): int
    {
        return $this->pageQuery($document, $excludePageId)->count() + 1;
    }

    protected function normalizePageSortOrders(Document $document, ?int $excludePageId = null): void
    {
        // TODO: This is intentionally simple but can issue N update queries. Consider a driver-specific bulk renumber if page counts grow.
        $pages = $this->pageQuery($document, $excludePageId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($pages as $index => $page) {
            $targetOrder = $index + 1;

            if ((int) $page->sort_order !== $targetOrder) {
                $page->update(['sort_order' => $targetOrder]);
            }
        }
    }

    protected function shiftPagesForInsert(Document $document, int $sortOrder, ?int $excludePageId = null): void
    {
        // TODO: This can be replaced with a bulk increment query if sort-order writes become a bottleneck.
        $pages = $this->pageQuery($document, $excludePageId)
            ->where('sort_order', '>=', $sortOrder)
            ->orderByDesc('sort_order')
            ->get();

        foreach ($pages as $page) {
            $page->update([
                'sort_order' => (int) $page->sort_order + 1,
            ]);
        }
    }

    protected function pageQuery(Document $document, ?int $excludePageId = null)
    {
        return DocumentPage::query()
            ->where('document_id', $document->id)
            ->when($excludePageId, fn ($query) => $query->whereKeyNot($excludePageId));
    }

    protected function documentSlugFromInput(?string $slugInput, string $fallbackTitle): string
    {
        return Str::slug(filled($slugInput) ? $slugInput : $fallbackTitle);
    }

    protected function documentPageSlugFromInput(?string $slugInput, string $fallbackTitle): string
    {
        return Str::slug(filled($slugInput) ? $slugInput : $fallbackTitle);
    }

    protected function slugExistsInEdition(Edition $edition, string $slug, ?int $ignoreDocumentId = null): bool
    {
        return Document::query()
            ->where('edition_id', $edition->id)
            ->where('slug', $slug)
            ->when($ignoreDocumentId, fn ($query) => $query->whereKeyNot($ignoreDocumentId))
            ->exists();
    }

    protected function availableImageAssets()
    {
        return MediaAsset::query()
            ->libraryItems()
            ->ofAssetType('image')
            ->withCount(['contentNodes', 'documentPages'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
    }
}
