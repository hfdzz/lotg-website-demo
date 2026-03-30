<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Law;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LawAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.laws.index', [
            'laws' => Law::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'law_number' => ['required', 'string', 'max:20'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:laws,slug'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $slug = $validated['slug'] ?: Str::slug('law-'.$validated['law_number']);

        $law = Law::create([
            'law_number' => $validated['law_number'],
            'slug' => $slug,
            'sort_order' => $validated['sort_order'],
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.laws.edit', $law)
            ->with('status', 'Law created.');
    }

    public function edit(Law $law): View
    {
        $law->load([
            'contentNodes.translations',
            'contentNodes.mediaAssets',
        ]);

        return view('admin.laws.edit', [
            'law' => $law,
            'nodeTree' => $this->buildNodeTree($law),
            'parentOptions' => $this->buildParentOptions($law),
        ]);
    }

    public function update(Request $request, Law $law): RedirectResponse
    {
        $validated = $request->validate([
            'law_number' => ['required', 'string', 'max:20'],
            'slug' => ['required', 'string', 'max:255', 'unique:laws,slug,'.$law->id],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $law->update($validated);

        return redirect()
            ->route('admin.laws.edit', $law)
            ->with('status', 'Law updated.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildParentOptions(Law $law): array
    {
        $law->loadMissing('contentNodes.translations');

        $childrenByParent = $law->contentNodes
            ->sortBy('sort_order')
            ->groupBy('parent_id');

        return $this->flattenNodes($childrenByParent, null, 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildNodeTree(Law $law): array
    {
        $law->loadMissing('contentNodes.translations');

        $childrenByParent = $law->contentNodes
            ->sortBy('sort_order')
            ->groupBy('parent_id');

        return $this->buildNodeTreeBranch($childrenByParent, null, 0);
    }

    /**
     * @param \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, \App\Models\ContentNode>> $childrenByParent
     * @return array<int, array<string, mixed>>
     */
    protected function buildNodeTreeBranch($childrenByParent, ?int $parentId, int $depth): array
    {
        return (($childrenByParent->get($parentId) ?? collect())->sortBy('sort_order'))
            ->map(function ($node) use ($childrenByParent, $depth) {
                $translation = $node->translationFor($this->defaultLanguage());

                return [
                    'id' => $node->id,
                    'title' => $translation?->title ?: ucfirst(str_replace('_', ' ', $node->node_type)),
                    'node_type' => $node->node_type,
                    'sort_order' => $node->sort_order,
                    'is_published' => $node->is_published,
                    'depth' => $depth,
                    'children' => $this->buildNodeTreeBranch($childrenByParent, $node->id, $depth + 1),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, \App\Models\ContentNode>> $childrenByParent
     * @return array<int, array<string, mixed>>
     */
    protected function flattenNodes($childrenByParent, ?int $parentId, int $depth): array
    {
        $items = [];

        foreach (($childrenByParent->get($parentId) ?? collect())->sortBy('sort_order') as $node) {
            $translation = $node->translationFor($this->defaultLanguage());

            $items[] = [
                'id' => $node->id,
                'label' => str_repeat('-- ', $depth).($translation?->title ?: ucfirst(str_replace('_', ' ', $node->node_type)).' #'.$node->id),
            ];

            $items = array_merge($items, $this->flattenNodes($childrenByParent, $node->id, $depth + 1));
        }

        return $items;
    }

    protected function defaultLanguage(): string
    {
        return config('app.fallback_locale', 'en');
    }
}
