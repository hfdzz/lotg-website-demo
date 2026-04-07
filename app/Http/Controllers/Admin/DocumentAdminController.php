<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentPage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.documents.index', [
            'documents' => Document::query()->with('pages')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:documents,slug'],
            'type' => ['required', 'in:single,collection'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $document = Document::create([
            'title' => $validated['title'],
            'slug' => filled($validated['slug'] ?? null) ? Str::slug($validated['slug']) : Str::slug($validated['title']),
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

        return redirect()->route('admin.documents.edit', ['document' => $document])->with('status', 'Document created.');
    }

    public function edit(Document $document): View
    {
        $document->load('pages');

        return view('admin.documents.edit', [
            'document' => $document,
        ]);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:documents,slug,'.$document->id],
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

        $document->update([
            'title' => $validated['title'],
            'slug' => filled($validated['slug'] ?? null) ? Str::slug($validated['slug']) : Str::slug($validated['title']),
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

        return redirect()->route('admin.documents.edit', ['document' => $document])->with('status', 'Document updated.');
    }
}
