<?php

namespace App\Services;

use App\Models\Law;
use Illuminate\Support\Collection;

class LawTreeBuilder
{
    public function build(Law $law, string $languageCode = 'en'): array
    {
        $nodes = $law->publishedContentNodes()
            ->with([
                'translations' => fn ($query) => $query->orderByRaw("CASE WHEN language_code = ? THEN 0 ELSE 1 END", [$languageCode]),
                'mediaAssets',
            ])
            ->orderBy('sort_order')
            ->get();

        $childrenByParent = $nodes->groupBy('parent_id');

        return $this->buildBranch($childrenByParent, null, 0, $languageCode);
    }

    /**
     * @param Collection<int, Collection<int, \App\Models\ContentNode>> $childrenByParent
     * @return array<int, array<string, mixed>>
     */
    protected function buildBranch(Collection $childrenByParent, ?int $parentId, int $depth, string $languageCode): array
    {
        return ($childrenByParent->get($parentId) ?? collect())
            ->sortBy('sort_order')
            ->values()
            ->map(function ($node) use ($childrenByParent, $depth, $languageCode) {
                $translation = $node->translationFor($languageCode);

                return [
                    'id' => $node->id,
                    'node_type' => $node->node_type,
                    'sort_order' => $node->sort_order,
                    'depth' => $depth,
                    'heading_tag' => $this->headingTagFor($node->node_type, $depth),
                    'settings' => $node->settings_json ?? [],
                    'translation' => $translation,
                    'title' => $translation?->title,
                    'body_html' => $translation?->body_html,
                    'media_items' => $this->buildMediaItems($node->mediaAssets),
                    'children' => $this->buildBranch($childrenByParent, $node->id, $depth + 1, $languageCode),
                ];
            })
            ->all();
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

    protected function buildMediaItems(Collection $mediaAssets): array
    {
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
}
