<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\Edition;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $slug = filled($validated['slug'] ?? null) ? Str::slug($validated['slug']) : Str::slug($baseTitle);

        if ($this->slugExistsInEdition($edition, $slug)) {
            return back()
                ->withErrors(['slug' => 'The slug has already been taken in this edition.'])
                ->withInput();
        }

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

        return redirect()->route('admin.documents.edit', ['edition' => $edition, 'document' => $document])->with('status', 'Document created.');
    }

    public function edit(Edition $edition, Document $document): View
    {
        $this->authorize('update', $document);
        abort_unless((int) $document->edition_id === (int) $edition->id, 404);
        $document->load(['translations', 'pages.translations']);

        return view('admin.documents.edit', [
            'document' => $document,
            'selectedEdition' => $edition,
            'editions' => Edition::query()->orderByDesc('year_start')->orderByDesc('year_end')->get(),
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
            'remove_page_ids' => ['nullable', 'array'],
            'remove_page_ids.*' => ['integer'],
        ]);

        $baseTitle = $validated['title_id'] ?: ($validated['title_en'] ?? '');
        $slug = filled($validated['slug'] ?? null) ? Str::slug($validated['slug']) : Str::slug($baseTitle);

        if ($this->slugExistsInEdition($edition, $slug, $document->id)) {
            return back()
                ->withErrors(['slug' => 'The slug has already been taken in this edition.'])
                ->withInput();
        }

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
        }

        foreach ($request->input('pages', []) as $index => $pageData) {
            $pageTitleId = trim((string) ($pageData['title_id'] ?? ''));
            $pageTitleEn = trim((string) ($pageData['title_en'] ?? ''));
            $pageSlug = trim((string) ($pageData['slug'] ?? ''));
            $pageBodyId = (string) ($pageData['body_html_id'] ?? '');
            $pageBodyEn = (string) ($pageData['body_html_en'] ?? '');
            $pageStatus = $pageData['status'] ?? $validated['status'];
            $pageSort = (int) ($pageData['sort_order'] ?? ($index + 1));

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
                continue;
            }

            $basePageTitle = $pageTitleId !== '' ? $pageTitleId : ($pageTitleEn !== '' ? $pageTitleEn : $document->displayTitle('id'));
            $basePageBody = trim($pageBodyId) !== '' ? $pageBodyId : (trim($pageBodyEn) !== '' ? $pageBodyEn : null);

            $payload = [
                'slug' => Str::slug($pageSlug !== '' ? $pageSlug : $basePageTitle),
                'title' => $basePageTitle,
                'body_html' => $basePageBody,
                'sort_order' => $pageSort > 0 ? $pageSort : ($index + 1),
                'status' => $pageStatus,
            ];

            if (! empty($pageData['id'])) {
                $page = $document->pages()->whereKey((int) $pageData['id'])->first();

                if ($page) {
                    $page->update($payload);
                    $this->syncDocumentPageTranslations($page, $pageData);
                }
            } else {
                $page = $document->pages()->create($payload);
                $this->syncDocumentPageTranslations($page, $pageData);
            }
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

    protected function slugExistsInEdition(Edition $edition, string $slug, ?int $ignoreDocumentId = null): bool
    {
        return Document::query()
            ->where('edition_id', $edition->id)
            ->where('slug', $slug)
            ->when($ignoreDocumentId, fn ($query) => $query->whereKeyNot($ignoreDocumentId))
            ->exists();
    }
}
