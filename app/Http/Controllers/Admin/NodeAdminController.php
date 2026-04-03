<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentNode;
use App\Models\ContentNodeTranslation;
use App\Models\Edition;
use App\Models\Law;
use App\Models\MediaAsset;
use App\Support\LotgLanguage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class NodeAdminController extends Controller
{
    public function edit(Edition $edition, Law $law, ContentNode $node): View
    {
        $this->assertLawBelongsToEdition($edition, $law);
        $this->assertNodeBelongsToLaw($law, $node);
        
        $node->load(['translations', 'mediaAssets', 'children']);
        
        return view('admin.nodes.edit', [
            'law' => $law,
            'node' => $node,
            'translationsByLanguage' => $node->translations->keyBy('language_code'),
            'languages' => LotgLanguage::supported(),
            'parentOptions' => $this->buildParentOptions($law, $node),
            'currentParentLabel' => $this->currentParentLabel($law, $node),
        ]);
    }

    public function store(Request $request, Edition $edition, Law $law): RedirectResponse
    {
        $this->assertLawBelongsToEdition($edition, $law);
        $validated = $this->validateNode($request, $law);

        $node = DB::transaction(function () use ($law, $validated, $request) {
            $parentId = $validated['parent_id'] ?: null;

            $node = ContentNode::create([
                'law_id' => $law->id,
                'parent_id' => $parentId,
                'node_type' => $validated['node_type'],
                'sort_order' => $this->nextSortOrder($law, $parentId),
                'is_published' => $request->boolean('is_published'),
                'settings_json' => $this->settingsFromRequest($request),
            ]);

            $this->syncTranslation($node, $validated);
            $this->syncMedia($request, $node);

            return $node;
        });

        return redirect()
            ->route('admin.nodes.edit', ['edition' => $edition, 'law' => $law, 'node' => $node])
            ->with('status', 'Node created.');
    }

    public function update(Request $request, Edition $edition, Law $law, ContentNode $node): RedirectResponse
    {
        $this->assertLawBelongsToEdition($edition, $law);
        $this->assertNodeBelongsToLaw($law, $node);

        $validated = $this->validateNode($request, $law, $node);

        DB::transaction(function () use ($node, $validated, $request, $law) {
            $oldParentId = $node->parent_id;
            $newParentId = $validated['parent_id'] ?: null;
            $requestedSortOrder = (int) $validated['sort_order'];

            $this->normalizeSiblingSortOrders($law, $oldParentId, $node->id);

            $maxSortOrder = $this->nextSortOrder($law, $newParentId, $node->id);
            $finalSortOrder = min(max($requestedSortOrder, 1), $maxSortOrder);

            $this->shiftSiblingsForInsert($law, $newParentId, $finalSortOrder, $node->id);

            $node->update([
                'parent_id' => $newParentId,
                'node_type' => $validated['node_type'],
                'sort_order' => $finalSortOrder,
                'is_published' => $request->boolean('is_published'),
                'settings_json' => $this->settingsFromRequest($request),
            ]);

            $this->syncTranslation($node, $validated);
            $this->syncMedia($request, $node);
        });

        return redirect()
            ->route('admin.nodes.edit', ['edition' => $edition, 'law' => $law, 'node' => $node])
            ->with('status', 'Node updated.');
    }

    public function destroy(Edition $edition, Law $law, ContentNode $node): RedirectResponse
    {
        $this->assertLawBelongsToEdition($edition, $law);
        $this->assertNodeBelongsToLaw($law, $node);

        DB::transaction(function () use ($node, $law) {
            $parentId = $node->parent_id;
            $this->deleteNodeRecursively($node);
            $this->normalizeSiblingSortOrders($law, $parentId);
        });

        return redirect()
            ->route('admin.laws.edit', ['edition' => $edition, 'law' => $law])
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
            'node_type' => ['required', 'in:section,rich_text,image,video_group,resource_list'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'title_id' => ['nullable', 'string', 'max:255'],
            'body_html_id' => ['nullable', 'string'],
            'translation_status_id' => ['required', 'in:draft,published'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'body_html_en' => ['nullable', 'string'],
            'translation_status_en' => ['required', 'in:draft,published'],
            'video_urls' => ['nullable', 'string'],
            'resource_lines' => ['nullable', 'string'],
            'resource_files' => ['nullable', 'array'],
            'resource_files.*' => ['nullable', 'file', 'max:10240'],
            'remove_resource_asset_ids' => ['nullable', 'array'],
            'remove_resource_asset_ids.*' => ['integer'],
            'image_caption' => ['nullable', 'string'],
            'image_credit' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    protected function syncTranslation(ContentNode $node, array $validated): void
    {
        foreach (array_keys(LotgLanguage::supported()) as $language) {
            ContentNodeTranslation::updateOrCreate(
                [
                    'content_node_id' => $node->id,
                    'language_code' => $language,
                ],
                [
                    'title' => $validated['title_'.$language] ?: null,
                    'body_html' => $validated['body_html_'.$language] ?: null,
                    'status' => $validated['translation_status_'.$language],
                ]
            );
        }
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

        if ($node->node_type === 'resource_list') {
            $this->syncResourceMedia($request, $node);

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

    protected function syncResourceMedia(Request $request, ContentNode $node): void
    {
        $existingAssets = $node->mediaAssets()->get();
        $removeAssetIds = collect($request->input('remove_resource_asset_ids', []))
            ->map(fn ($id) => (int) $id)
            ->all();

        $keptUploadAssets = $existingAssets
            ->filter(fn (MediaAsset $asset) => $asset->storage_type === 'upload' && ! in_array($asset->id, $removeAssetIds, true))
            ->values();

        $this->purgeNodeMedia($node, $keptUploadAssets->pluck('id')->all());

        $assetsForSync = collect();

        foreach ($this->parseResourceLines((string) $request->input('resource_lines')) as $resource) {
            $assetsForSync->push(MediaAsset::create([
                'asset_type' => $resource['asset_type'],
                'storage_type' => 'external',
                'external_url' => $resource['url'],
                'caption' => $resource['label'],
            ]));
        }

        foreach ($keptUploadAssets as $asset) {
            $assetsForSync->push($asset);
        }

        foreach ($request->file('resource_files', []) as $uploadedFile) {
            $path = $uploadedFile->store('lotg-resources', 'public');

            $assetsForSync->push(MediaAsset::create([
                'asset_type' => $this->uploadedFileAssetType($uploadedFile->getClientOriginalName()),
                'storage_type' => 'upload',
                'file_path' => $path,
                'caption' => $uploadedFile->getClientOriginalName(),
            ]));
        }

        $syncPayload = [];

        foreach ($assetsForSync->values() as $index => $asset) {
            $syncPayload[$asset->id] = ['sort_order' => $index + 1];
        }

        $node->mediaAssets()->sync($syncPayload);
    }

    protected function settingsFromRequest(Request $request): ?array
    {
        if ($request->input('node_type') === 'video_group') {
            return [
                'layout' => 'stacked',
            ];
        }

        if ($request->input('node_type') === 'resource_list') {
            return [
                'layout' => 'list',
            ];
        }

        return null;
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

    protected function nextSortOrder(Law $law, ?int $parentId, ?int $excludeNodeId = null): int
    {
        return $this->siblingQuery($law, $parentId, $excludeNodeId)->count() + 1;
    }

    protected function normalizeSiblingSortOrders(Law $law, ?int $parentId, ?int $excludeNodeId = null): void
    {
        $siblings = $this->siblingQuery($law, $parentId, $excludeNodeId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($siblings as $index => $sibling) {
            $targetOrder = $index + 1;

            if ((int) $sibling->sort_order !== $targetOrder) {
                $sibling->update(['sort_order' => $targetOrder]);
            }
        }
    }

    protected function shiftSiblingsForInsert(Law $law, ?int $parentId, int $sortOrder, ?int $excludeNodeId = null): void
    {
        $siblings = $this->siblingQuery($law, $parentId, $excludeNodeId)
            ->where('sort_order', '>=', $sortOrder)
            ->orderByDesc('sort_order')
            ->get();

        foreach ($siblings as $sibling) {
            $sibling->update([
                'sort_order' => (int) $sibling->sort_order + 1,
            ]);
        }
    }

    protected function siblingQuery(Law $law, ?int $parentId, ?int $excludeNodeId = null)
    {
        return ContentNode::query()
            ->where('law_id', $law->id)
            ->where('parent_id', $parentId)
            ->when($excludeNodeId, fn ($query) => $query->whereKeyNot($excludeNodeId));
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

    protected function assertLawBelongsToEdition(Edition $edition, Law $law): void
    {
        abort_unless((int) $law->edition_id === (int) $edition->id, 404);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildParentOptions(Law $law, ContentNode $currentNode): array
    {
        $excludedIds = $this->descendantIds($currentNode)->push($currentNode->id)->all();
        $childrenByParent = $law->loadMissing('contentNodes.translations')->contentNodes
            ->reject(fn (ContentNode $node) => in_array($node->id, $excludedIds, true))
            ->sortBy('sort_order')
            ->groupBy('parent_id');

        return $this->flattenNodes($childrenByParent, null, 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildParentOptionsForLaw(Law $law): array
    {
        $childrenByParent = $law->loadMissing('contentNodes.translations')->contentNodes
            ->sortBy('sort_order')
            ->groupBy('parent_id');

        return $this->flattenNodes($childrenByParent, null, 0);
    }

    protected function currentParentLabel(Law $law, ContentNode $node): string
    {
        if (! $node->parent_id) {
            return 'Root level';
        }

        $parent = collect($this->buildParentOptionsForLaw($law))->firstWhere('id', $node->parent_id);

        return $parent['label'] ?? 'Unknown parent';
    }

    /**
     * @param \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, \App\Models\ContentNode>> $childrenByParent
     * @return array<int, array<string, mixed>>
     */
    protected function flattenNodes($childrenByParent, ?int $parentId, int $depth): array
    {
        $items = [];

        foreach (($childrenByParent->get($parentId) ?? collect())->sortBy('sort_order') as $node) {
            $translation = $node->translationFor(LotgLanguage::default());

            $items[] = [
                'id' => $node->id,
                'label' => str_repeat('-- ', $depth)
                    .'['.strtoupper($node->node_type).'] '
                    .($translation?->title ?: ucfirst(str_replace('_', ' ', $node->node_type)).' #'.$node->id)
                    .' (sort '.$node->sort_order.')',
            ];

            $items = array_merge($items, $this->flattenNodes($childrenByParent, $node->id, $depth + 1));
        }

        return $items;
    }

    /**
     * @return array<int, array{asset_type: string, label: string, url: string}>
     */
    protected function parseResourceLines(string $resourceLines): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $resourceLines))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->map(function (string $line) {
                $parts = array_map('trim', explode('|', $line));

                if (count($parts) >= 3) {
                    [$type, $label, $url] = [$parts[0], $parts[1], $parts[2]];
                } elseif (count($parts) === 2) {
                    [$label, $url] = [$parts[0], $parts[1]];
                    $type = '';
                } else {
                    return null;
                }

                if ($label === '' || $url === '') {
                    return null;
                }

                return [
                    'asset_type' => $this->normalizeResourceAssetType($type, $url),
                    'label' => $label,
                    'url' => $url,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeResourceAssetType(string $type, string $url): string
    {
        $normalized = str($type)->lower()->replace(' ', '_')->value();

        if (in_array($normalized, ['document', 'external_link', 'video_link', 'file'], true)) {
            return $normalized;
        }

        if (MediaAsset::parseYouTubeId($url)) {
            return 'video_link';
        }

        if (preg_match('/\.(pdf|doc|docx|xls|xlsx|ppt|pptx)$/i', $url)) {
            return 'document';
        }

        return 'external_link';
    }

    protected function uploadedFileAssetType(string $filename): string
    {
        return preg_match('/\.(pdf|doc|docx|xls|xlsx|ppt|pptx)$/i', $filename)
            ? 'document'
            : 'file';
    }

}
