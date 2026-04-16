<?php

namespace App\Services;

use App\Models\ChangelogEntry;
use App\Models\ContentNode;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\Edition;
use App\Models\Law;
use App\Models\LawQa;
use App\Models\MediaAsset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class EditionJsonExporter
{
    public const SCHEMA_VERSION = 1;

    public function export(Edition $edition): array
    {
        $documents = Document::query()
            ->forEdition($edition->id)
            ->with(['translations', 'pages.translations'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $laws = Law::query()
            ->forEdition($edition->id)
            ->with([
                'translations',
                'contentNodes.translations',
                'contentNodes.mediaAssets',
                'qas.translations',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $mediaAssets = $laws
            ->flatMap(fn (Law $law) => $law->contentNodes->flatMap(fn (ContentNode $node) => $node->mediaAssets))
            ->unique('id')
            ->sortBy('id')
            ->values();

        $changelogEntries = ChangelogEntry::query()
            ->where('edition_id', $edition->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $mediaKeyById = $mediaAssets
            ->mapWithKeys(fn (MediaAsset $mediaAsset) => [$mediaAsset->id => $this->mediaKey($mediaAsset)])
            ->all();

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'exported_at' => now()->toIso8601String(),
            'edition' => [
                'name' => $edition->name,
                'code' => $edition->code,
                'year_start' => $edition->year_start,
                'year_end' => $edition->year_end,
                'status' => $edition->status,
                'is_active' => $edition->is_active,
            ],
            'media_assets' => $mediaAssets
                ->map(fn (MediaAsset $mediaAsset) => $this->exportMediaAsset($mediaAsset))
                ->values()
                ->all(),
            'changelog_entries' => $changelogEntries
                ->map(fn (ChangelogEntry $entry) => [
                    'language_code' => $entry->language_code,
                    'title' => $entry->title,
                    'body' => $entry->body,
                    'sort_order' => $entry->sort_order,
                    'published_at' => $entry->published_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'laws' => $laws
                ->map(fn (Law $law) => $this->exportLaw($law, $mediaKeyById))
                ->values()
                ->all(),
            'documents' => $documents
                ->map(fn (Document $document) => $this->exportDocument($document))
                ->values()
                ->all(),
        ];
    }

    protected function exportLaw(Law $law, array $mediaKeyById): array
    {
        $nodesByParent = $law->contentNodes
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->groupBy('parent_id');

        return [
            'law_number' => $law->law_number,
            'slug' => $law->slug,
            'sort_order' => $law->sort_order,
            'status' => $law->status,
            'translations' => $this->exportTranslations($law->translations, [
                'title',
                'subtitle',
                'description_text',
            ]),
            'nodes' => $this->exportNodeBranch($nodesByParent, null, $mediaKeyById),
            'qas' => $law->qas
                ->sortBy([
                    ['sort_order', 'asc'],
                    ['id', 'asc'],
                ])
                ->map(fn (LawQa $qa) => [
                    'sort_order' => $qa->sort_order,
                    'is_published' => $qa->is_published,
                    'translations' => $this->exportTranslations($qa->translations, [
                        'question',
                        'answer_html',
                        'status',
                    ]),
                ])
                ->values()
                ->all(),
        ];
    }

    protected function exportDocument(Document $document): array
    {
        return [
            'slug' => $document->slug,
            'type' => $document->type,
            'sort_order' => $document->sort_order,
            'status' => $document->status,
            'base_title' => $document->title,
            'translations' => $this->exportTranslations($document->translations, [
                'title',
            ]),
            'pages' => $document->pages
                ->sortBy([
                    ['sort_order', 'asc'],
                    ['id', 'asc'],
                ])
                ->map(fn (DocumentPage $page) => [
                    'slug' => $page->slug,
                    'sort_order' => $page->sort_order,
                    'status' => $page->status,
                    'base_title' => $page->title,
                    'base_body_html' => $page->body_html,
                    'translations' => $this->exportTranslations($page->translations, [
                        'title',
                        'body_html',
                    ]),
                ])
                ->values()
                ->all(),
        ];
    }

    protected function exportNodeBranch(Collection $nodesByParent, ?int $parentId, array $mediaKeyById): array
    {
        return (($nodesByParent->get($parentId) ?? collect())->sortBy([
            ['sort_order', 'asc'],
            ['id', 'asc'],
        ]))
            ->map(function (ContentNode $node) use ($nodesByParent, $mediaKeyById) {
                return [
                    'node_type' => $node->node_type,
                    'sort_order' => $node->sort_order,
                    'is_published' => $node->is_published,
                    'settings_json' => $node->settings_json,
                    'translations' => $this->exportTranslations($node->translations, [
                        'title',
                        'body_html',
                        'status',
                    ]),
                    'media' => $node->mediaAssets
                        ->sortBy(fn (MediaAsset $mediaAsset) => (int) ($mediaAsset->pivot->sort_order ?? 1))
                        ->map(fn (MediaAsset $mediaAsset) => [
                            'media_key' => $mediaKeyById[$mediaAsset->id] ?? $this->mediaKey($mediaAsset),
                            'sort_order' => (int) ($mediaAsset->pivot->sort_order ?? 1),
                        ])
                        ->values()
                        ->all(),
                    'children' => $this->exportNodeBranch($nodesByParent, $node->id, $mediaKeyById),
                ];
            })
            ->values()
            ->all();
    }

    protected function exportTranslations(iterable $translations, array $fields): array
    {
        return collect($translations)
            ->mapWithKeys(function ($translation) use ($fields) {
                $payload = [];

                foreach ($fields as $field) {
                    $payload[$field] = $translation->{$field};
                }

                return [$translation->language_code => $payload];
            })
            ->all();
    }

    protected function exportMediaAsset(MediaAsset $mediaAsset): array
    {
        return [
            'key' => $this->mediaKey($mediaAsset),
            'asset_type' => $mediaAsset->asset_type,
            'storage_type' => $mediaAsset->storage_type,
            'is_library_item' => $mediaAsset->is_library_item,
            'file_path' => $mediaAsset->file_path,
            'external_url' => $mediaAsset->external_url,
            'thumbnail_path' => $mediaAsset->thumbnail_path,
            'caption' => $mediaAsset->caption,
            'credit' => $mediaAsset->credit,
            'file' => $this->exportStoredFile($mediaAsset->file_path, $mediaAsset->storage_type === 'upload'),
            'thumbnail_file' => $this->exportStoredFile($mediaAsset->thumbnail_path, filled($mediaAsset->thumbnail_path)),
        ];
    }

    protected function exportStoredFile(?string $path, bool $shouldEmbed): ?array
    {
        if (! $path) {
            return null;
        }

        $payload = ['path' => $path];

        if (! $shouldEmbed || str_starts_with($path, 'demo/')) {
            return $payload;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $payload;
        }

        if (! Storage::disk('public')->exists($path)) {
            return $payload;
        }

        $payload['contents_base64'] = base64_encode(Storage::disk('public')->get($path));

        return $payload;
    }

    protected function mediaKey(MediaAsset $mediaAsset): string
    {
        return 'media_'.$mediaAsset->id;
    }
}
