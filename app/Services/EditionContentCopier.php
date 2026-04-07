<?php

namespace App\Services;

use App\Models\ContentNode;
use App\Models\ContentNodeTranslation;
use App\Models\Edition;
use App\Models\Law;
use App\Models\LawQa;
use App\Models\LawQaTranslation;
use App\Models\LawTranslation;
use App\Support\UniqueSlugSuffixer;
use Illuminate\Support\Str;

class EditionContentCopier
{
    public function copy(Edition $sourceEdition, Edition $targetEdition): void
    {
        $laws = Law::query()
            ->where('edition_id', $sourceEdition->id)
            ->with([
                'translations',
                'contentNodes.translations',
                'contentNodes.mediaAssets',
                'qas.translations',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($laws as $sourceLaw) {
            $newLaw = Law::create([
                'edition_id' => $targetEdition->id,
                'law_number' => $sourceLaw->law_number,
                'slug' => $this->makeCopiedLawSlug($sourceLaw->slug, $targetEdition),
                'sort_order' => $sourceLaw->sort_order,
                'status' => $sourceLaw->status,
            ]);

            foreach ($sourceLaw->translations as $translation) {
                LawTranslation::create([
                    'law_id' => $newLaw->id,
                    'language_code' => $translation->language_code,
                    'title' => $translation->title,
                    'subtitle' => $translation->subtitle,
                    'description_text' => $translation->description_text,
                ]);
            }

            $nodeIdMap = [];
            $sourceNodes = $sourceLaw->contentNodes->sortBy([
                ['parent_id', 'asc'],
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ]);

            foreach ($sourceNodes as $sourceNode) {
                $newNode = ContentNode::create([
                    'law_id' => $newLaw->id,
                    'parent_id' => $sourceNode->parent_id ? ($nodeIdMap[$sourceNode->parent_id] ?? null) : null,
                    'node_type' => $sourceNode->node_type,
                    'sort_order' => $sourceNode->sort_order,
                    'is_published' => $sourceNode->is_published,
                    'settings_json' => $sourceNode->settings_json,
                ]);

                $nodeIdMap[$sourceNode->id] = $newNode->id;

                foreach ($sourceNode->translations as $translation) {
                    ContentNodeTranslation::create([
                        'content_node_id' => $newNode->id,
                        'language_code' => $translation->language_code,
                        'title' => $translation->title,
                        'body_html' => $translation->body_html,
                        'status' => $translation->status,
                    ]);
                }

                $syncPayload = [];

                foreach ($sourceNode->mediaAssets as $mediaAsset) {
                    $syncPayload[$mediaAsset->id] = [
                        'sort_order' => (int) ($mediaAsset->pivot->sort_order ?? 1),
                    ];
                }

                if ($syncPayload !== []) {
                    $newNode->mediaAssets()->sync($syncPayload);
                }
            }

            foreach ($sourceLaw->qas->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ]) as $sourceQa) {
                $newQa = LawQa::create([
                    'law_id' => $newLaw->id,
                    'sort_order' => $sourceQa->sort_order,
                    'is_published' => $sourceQa->is_published,
                ]);

                foreach ($sourceQa->translations as $translation) {
                    LawQaTranslation::create([
                        'law_qa_id' => $newQa->id,
                        'language_code' => $translation->language_code,
                        'question' => $translation->question,
                        'answer_html' => $translation->answer_html,
                        'status' => $translation->status,
                    ]);
                }
            }
        }
    }

    protected function makeCopiedLawSlug(string $sourceSlug, Edition $targetEdition): string
    {
        return UniqueSlugSuffixer::ensureUnique(
            Str::slug($sourceSlug.'-'.$targetEdition->code),
            fn (string $candidate) => Law::query()->where('slug', $candidate)->exists()
        );
    }
}
