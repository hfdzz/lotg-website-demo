<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentNode;
use App\Models\ContentNodeTranslation;
use App\Models\Law;
use App\Models\MediaAsset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NodeAdminController extends Controller
{
    public function edit(Law $law, ContentNode $node): View
    {
        $this->assertNodeBelongsToLaw($law, $node);
        
        $node->load(['translations', 'mediaAssets', 'children']);
        
        return view('admin.nodes.edit', [
            'law' => $law,
            'node' => $node,
            'translation' => $node->translationFor($this->defaultLanguage()),
            'parentOptions' => $this->buildParentOptions($law, $node),
        ]);
    }

    public function store(Request $request, Law $law): RedirectResponse
    {
        $validated = $this->validateNode($request, $law);

        $node = ContentNode::create([
            'law_id' => $law->id,
            'parent_id' => $validated['parent_id'] ?: null,
            'node_type' => $validated['node_type'],
            'sort_order' => $validated['sort_order'],
            'is_published' => $request->boolean('is_published'),
            'settings_json' => $this->settingsFromRequest($request),
        ]);

        $this->syncTranslation($node, $validated);
        $this->syncMedia($request, $node);

        return redirect()
            ->route('admin.nodes.edit', [$law, $node])
            ->with('status', 'Node created.');
    }

    public function update(Request $request, Law $law, ContentNode $node): RedirectResponse
    {
        $this->assertNodeBelongsToLaw($law, $node);

        $validated = $this->validateNode($request, $law, $node);

        $node->update([
            'parent_id' => $validated['parent_id'] ?: null,
            'node_type' => $validated['node_type'],
            'sort_order' => $validated['sort_order'],
            'is_published' => $request->boolean('is_published'),
            'settings_json' => $this->settingsFromRequest($request),
        ]);

        $this->syncTranslation($node, $validated);
        $this->syncMedia($request, $node);

        return redirect()
            ->route('admin.nodes.edit', [$law, $node])
            ->with('status', 'Node updated.');
    }

    public function destroy(Law $law, ContentNode $node): RedirectResponse
    {
        $this->assertNodeBelongsToLaw($law, $node);

        $this->deleteNodeRecursively($node);

        return redirect()
            ->route('admin.laws.edit', $law)
            ->with('status', 'Node deleted.');
    }

    protected function validateNode(Request $request, Law $law, ?ContentNode $node = null): array
    {
        $blockedParentIds = $node ? $this->descendantIds($node)->push($node->id)->all() : [];

        return $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                'exists:content_nodes,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($law, $blockedParentIds) {
                    if (! $value) {
                        return;
                    }

                    $parent = ContentNode::find($value);

                    if (! $parent || (int) $parent->law_id !== (int) $law->id) {
                        $fail('Parent node must belong to the same law.');
                    }

                    if (in_array((int) $value, $blockedParentIds, true)) {
                        $fail('Parent node cannot be the node itself or one of its descendants.');
                    }
                },
            ],
            'node_type' => ['required', 'in:section,rich_text,image,video_group'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'title' => ['nullable', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
            'translation_status' => ['required', 'in:draft,published'],
            'video_urls' => ['nullable', 'string'],
            'image_caption' => ['nullable', 'string'],
            'image_credit' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    protected function syncTranslation(ContentNode $node, array $validated): void
    {
        ContentNodeTranslation::updateOrCreate(
            [
                'content_node_id' => $node->id,
                'language_code' => $this->defaultLanguage(),
            ],
            [
                'title' => $validated['title'] ?: null,
                'body_html' => $validated['body_html'] ?: null,
                'status' => $validated['translation_status'],
            ]
        );
    }

    protected function syncMedia(Request $request, ContentNode $node): void
    {
        if ($node->node_type === 'image') {
            $this->syncImageMedia($request, $node);

            return;
        }

        if ($node->node_type === 'video_group') {
            $this->syncVideoMedia($request, $node);

            return;
        }

        $this->purgeNodeMedia($node);
    }

    protected function syncImageMedia(Request $request, ContentNode $node): void
    {
        $currentImage = $node->mediaAssets()->where('asset_type', 'image')->first();
        $path = null;

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('lotg-images', 'public');
        } else {
            $path = $currentImage?->file_path;
        }

        if (! $path) {
            $this->purgeNodeMedia($node);

            return;
        }

        $this->purgeNodeMedia($node, $currentImage ? [$currentImage->id] : []);

        if ($currentImage) {
            $currentImage->update([
                'asset_type' => 'image',
                'storage_type' => 'upload',
                'file_path' => $path,
                'caption' => $request->input('image_caption'),
                'credit' => $request->input('image_credit'),
            ]);

            $asset = $currentImage;
        } else {
            $asset = MediaAsset::create([
                'asset_type' => 'image',
                'storage_type' => 'upload',
                'file_path' => $path,
                'caption' => $request->input('image_caption'),
                'credit' => $request->input('image_credit'),
            ]);
        }

        $node->mediaAssets()->sync([
            $asset->id => ['sort_order' => 1],
        ]);
    }

    protected function syncVideoMedia(Request $request, ContentNode $node): void
    {
        $urls = collect(preg_split('/\r\n|\r|\n/', (string) $request->input('video_urls')))
            ->map(fn ($url) => trim($url))
            ->filter()
            ->values();

        $this->purgeNodeMedia($node);

        if ($urls->isEmpty()) {
            return;
        }

        $syncPayload = [];

        foreach ($urls as $index => $url) {
            $asset = MediaAsset::create([
                'asset_type' => 'video',
                'storage_type' => 'youtube',
                'external_url' => $url,
                'caption' => 'Video '.($index + 1),
            ]);

            $syncPayload[$asset->id] = ['sort_order' => $index + 1];
        }

        $node->mediaAssets()->sync($syncPayload);
    }

    protected function settingsFromRequest(Request $request): ?array
    {
        if ($request->input('node_type') !== 'video_group') {
            return null;
        }

        return [
            'layout' => 'stacked',
        ];
    }

    protected function deleteNodeRecursively(ContentNode $node): void
    {
        $node->loadMissing('children');

        foreach ($node->children as $child) {
            $this->deleteNodeRecursively($child);
        }

        $this->purgeNodeMedia($node);
        $node->delete();
    }

    /**
     * @param array<int, int> $keepAssetIds
     */
    protected function purgeNodeMedia(ContentNode $node, array $keepAssetIds = []): void
    {
        $assets = $node->mediaAssets()->get();

        if ($assets->isEmpty()) {
            return;
        }

        $detachIds = $assets
            ->reject(fn (MediaAsset $asset) => in_array($asset->id, $keepAssetIds, true))
            ->pluck('id');

        if ($detachIds->isEmpty()) {
            return;
        }

        $node->mediaAssets()->detach($detachIds);

        foreach ($assets->whereIn('id', $detachIds) as $asset) {
            if ($asset->contentNodes()->count() === 0) {
                $asset->delete();
            }
        }
    }

    /**
     * @return Collection<int, int>
     */
    protected function descendantIds(ContentNode $node): Collection
    {
        $node->loadMissing('children.children.children.children');

        $ids = collect();

        foreach ($node->children as $child) {
            $ids->push($child->id);
            $ids = $ids->merge($this->descendantIds($child));
        }

        return $ids->unique()->values();
    }

    protected function assertNodeBelongsToLaw(Law $law, ContentNode $node): void
    {
        abort_unless((int) $node->law_id === (int) $law->id, 404);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildParentOptions(Law $law, ContentNode $currentNode): array
    {
        $law->loadMissing('contentNodes.translations');

        $excludedIds = $this->descendantIds($currentNode)->push($currentNode->id)->all();
        $childrenByParent = $law->contentNodes
            ->reject(fn (ContentNode $node) => in_array($node->id, $excludedIds, true))
            ->sortBy('sort_order')
            ->groupBy('parent_id');

        return $this->flattenNodes($childrenByParent, null, 0);
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
