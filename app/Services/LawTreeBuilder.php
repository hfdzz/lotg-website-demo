<?php

namespace App\Services;

use App\Models\Law;
use Illuminate\Support\Collection;

class LawTreeBuilder
{
    public function build(Law $law, string $languageCode = 'id'): array
    {
        $nodes = $law->publishedContentNodes()
            ->with([
                'translations' => fn ($query) => $query->orderByRaw("CASE WHEN language_code = ? THEN 0 ELSE 1 END", [$languageCode]),
                'mediaAssets',
            ])
            ->orderBy('sort_order')
            ->get();

        $childrenByParent = $nodes->groupBy('parent_id');

        return $this->buildBranch($childrenByParent, null, 0, $languageCode, [(string) $law->law_number]);
    }

    /**
     * @param Collection<int, Collection<int, \App\Models\ContentNode>> $childrenByParent
     * @return array<int, array<string, mixed>>
     */
    protected function buildBranch(Collection $childrenByParent, ?int $parentId, int $depth, string $languageCode, array $sectionPath): array
    {
        $sectionIndex = 0;

        return ($childrenByParent->get($parentId) ?? collect())
            ->sortBy('sort_order')
            ->values()
            ->map(function ($node) use ($childrenByParent, $depth, $languageCode, $sectionPath, &$sectionIndex) {
                $translation = $node->translationFor($languageCode);
                $currentSectionPath = $sectionPath;
                $sectionNumber = null;

                if ($node->node_type === 'section') {
                    $sectionIndex++;
                    $currentSectionPath[] = (string) $sectionIndex;
                    $sectionNumber = implode('.', $currentSectionPath);
                }

                return [
                    'id' => $node->id,
                    'node_type' => $node->node_type,
                    'sort_order' => $node->sort_order,
                    'depth' => $depth,
                    'section_number' => $sectionNumber,
                    'heading_tag' => $this->headingTagFor($node->node_type, $depth),
                    'anchor_id' => $this->anchorIdFor($node->id, $translation?->title, $node->node_type),
                    'settings' => $node->settings_json ?? [],
                    'translation' => $translation,
                    'title' => $translation?->title,
                    'body_html' => $translation?->body_html,
                    'media_items' => $this->buildMediaItems($node->mediaAssets, $node->node_type),
                    'resource_items' => $this->buildResourceItems($node->mediaAssets, $node->node_type),
                    'children' => $this->buildBranch($childrenByParent, $node->id, $depth + 1, $languageCode, $currentSectionPath),
                ];
            })
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $tree
     * @return array<int, array<string, mixed>>
     */
    public function buildTableOfContents(array $tree): array
    {
        $items = [];

        foreach ($tree as $node) {
            $children = $this->buildTableOfContents($node['children'] ?? []);

            if ($node['node_type'] !== 'section' || ! ($node['title'] ?? null)) {
                $items = array_merge($items, $children);
                continue;
            }

            $items[] = [
                'title' => $node['title'],
                'section_number' => $node['section_number'] ?? null,
                'anchor_id' => $node['anchor_id'],
                'depth' => $node['depth'],
                'children' => $children,
            ];
        }

        return $items;
    }

    protected function headingTagFor(string $nodeType, int $depth): string
    {
        if ($nodeType !== 'section') {
            return 'h3';
        }

        return match (min($depth, 3)) {
            0 => 'h2',
            1 => 'h3',
            2 => 'h4',
            default => 'h5',
        };
    }

    protected function buildMediaItems(Collection $mediaAssets, string $nodeType): array
    {
        if (! in_array($nodeType, ['image', 'video_group'], true)) {
            return [];
        }

        return $mediaAssets
            ->map(function ($mediaAsset) {
                if ($mediaAsset->asset_type === 'image' && $mediaAsset->publicUrl()) {
                    return [
                        'kind' => 'image',
                        'src' => $mediaAsset->publicUrl(),
                        'caption' => $mediaAsset->caption,
                        'credit' => $mediaAsset->credit,
                    ];
                }

                if ($mediaAsset->asset_type === 'video' && $mediaAsset->youtubeEmbedUrl()) {
                    return [
                        'kind' => 'video',
                        'src' => $mediaAsset->youtubeEmbedUrl(),
                        'caption' => $mediaAsset->caption,
                        'credit' => $mediaAsset->credit,
                    ];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function buildResourceItems(Collection $mediaAssets, string $nodeType): array
    {
        if ($nodeType !== 'resource_list') {
            return [];
        }

        return $mediaAssets
            ->map(function ($mediaAsset) {
                $url = $mediaAsset->resourceUrl();

                if (! $url) {
                    return null;
                }

                return [
                    'kind' => $mediaAsset->asset_type,
                    'url' => $url,
                    'label' => $mediaAsset->resourceLabel(),
                    'meta' => $mediaAsset->resourceKindLabel(),
                    'credit' => $mediaAsset->credit,
                    'is_external' => str_starts_with($url, 'http://') || str_starts_with($url, 'https://'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function anchorIdFor(int $nodeId, ?string $title, string $nodeType): string
    {
        $base = str($title ?: $nodeType)
            ->lower()
            ->slug();

        return trim($base !== '' ? $base.'-'.$nodeId : 'node-'.$nodeId, '-');
    }
}
