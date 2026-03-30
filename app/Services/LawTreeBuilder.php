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
                return [
                    'id' => $node->id,
                    'node_type' => $node->node_type,
                    'sort_order' => $node->sort_order,
                    'depth' => $depth,
                    'settings' => $node->settings_json ?? [],
                    'translation' => $node->translationFor($languageCode),
                    'media_assets' => $node->mediaAssets->values(),
                    'children' => $this->buildBranch($childrenByParent, $node->id, $depth + 1, $languageCode),
                ];
            })
            ->all();
    }
}
