<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
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
                ->with('pages')
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
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:single,collection'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $slug = filled($validated['slug'] ?? null) ? Str::slug($validated['slug']) : Str::slug($validated['title']);

        if ($this->slugExistsInEdition($edition, $slug)) {
            return back()
                ->withErrors(['slug' => 'The slug has already been taken in this edition.'])
                ->withInput();
        }

        $document = Document::create([
            'edition_id' => $edition->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'type' => $validated['type'],
            'sort_order' => $validated['sort_order'],
            'status' => $validated['status'],
        ]);

        if ($document->type === 'single') {
            $document->pages()->create([
                'slug' => 'overview',
                'title' => $document->title,
                'body_html' => null,
                'sort_order' => 1,
                'status' => $document->status,
            ]);
        }

        return redirect()->route('admin.documents.edit', ['edition' => $edition, 'document' => $document])->with('status', 'Document created.');
    }

    public function edit(Edition $edition, Document $document): View
    {
        $this->authorize('update', $document);
        abort_unless((int) $document->edition_id === (int) $edition->id, 404);
        $document->load('pages');

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
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:single,collection'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,published'],
            'pages' => ['nullable', 'array'],
            'pages.*.id' => ['nullable', 'integer'],
            'pages.*.slug' => ['nullable', 'string', 'max:255'],
            'pages.*.title' => ['nullable', 'string', 'max:255'],
            'pages.*.body_html' => ['nullable', 'string'],
            'pages.*.sort_order' => ['nullable', 'integer', 'min:1'],
            'pages.*.status' => ['nullable', 'in:draft,published'],
            'remove_page_ids' => ['nullable', 'array'],
            'remove_page_ids.*' => ['integer'],
        ]);

        $slug = filled($validated['slug'] ?? null) ? Str::slug($validated['slug']) : Str::slug($validated['title']);

        if ($this->slugExistsInEdition($edition, $slug, $document->id)) {
            return back()
                ->withErrors(['slug' => 'The slug has already been taken in this edition.'])
                ->withInput();
        }

        $document->update([
            'title' => $validated['title'],
            'slug' => $slug,
            'type' => $validated['type'],
            'sort_order' => $validated['sort_order'],
            'status' => $validated['status'],
        ]);

        $removeIds = collect($request->input('remove_page_ids', []))->map(fn ($id) => (int) $id)->all();
        if ($removeIds) {
            $document->pages()->whereIn('id', $removeIds)->delete();
        }

        foreach ($request->input('pages', []) as $index => $pageData) {
            $pageTitle = trim((string) ($pageData['title'] ?? ''));
            $pageSlug = trim((string) ($pageData['slug'] ?? ''));
            $pageBody = (string) ($pageData['body_html'] ?? '');
            $pageStatus = $pageData['status'] ?? $validated['status'];
            $pageSort = (int) ($pageData['sort_order'] ?? ($index + 1));

            if ($document->type === 'single') {
                $pageTitle = $pageTitle !== '' ? $pageTitle : $document->title;
                $pageSlug = $pageSlug !== '' ? $pageSlug : 'overview';
            }

            if ($pageTitle === '' && trim($pageBody) === '' && $pageSlug === '') {
                continue;
            }

            $payload = [
                'slug' => Str::slug($pageSlug !== '' ? $pageSlug : $pageTitle),
                'title' => $pageTitle !== '' ? $pageTitle : $document->title,
                'body_html' => $pageBody !== '' ? $pageBody : null,
                'sort_order' => $pageSort > 0 ? $pageSort : ($index + 1),
                'status' => $pageStatus,
            ];

            if (! empty($pageData['id'])) {
                $page = $document->pages()->whereKey((int) $pageData['id'])->first();
                if ($page) {
                    $page->update($payload);
                }
            } else {
                $document->pages()->create($payload);
            }
        }

        if ($document->type === 'single' && $document->pages()->count() === 0) {
            $document->pages()->create([
                'slug' => 'overview',
                'title' => $document->title,
                'body_html' => null,
                'sort_order' => 1,
                'status' => $document->status,
            ]);
        }

        return redirect()->route('admin.documents.edit', ['edition' => $edition, 'document' => $document])->with('status', 'Document updated.');
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
