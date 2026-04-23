<?php

namespace App\Services;

use App\Models\ContentNode;
use App\Models\ContentNodeTranslation;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\DocumentPageTranslation;
use App\Models\DocumentTranslation;
use App\Models\Edition;
use App\Models\Law;
use App\Models\LawQa;
use App\Models\LawQaOption;
use App\Models\LawQaOptionTranslation;
use App\Models\LawQaTranslation;
use App\Models\LawTranslation;
use App\Support\UniqueSlugSuffixer;
use Illuminate\Support\Str;

class EditionContentCopier
{
    public function copy(Edition $sourceEdition, Edition $targetEdition): void
    {
        $documents = Document::query()
            ->where('edition_id', $sourceEdition->id)
            ->with(['translations', 'pages.translations'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($documents as $sourceDocument) {
            $newDocument = Document::create([
                'edition_id' => $targetEdition->id,
                'slug' => $this->makeCopiedDocumentSlug($sourceDocument->slug, $targetEdition),
                'title' => $sourceDocument->title,
                'type' => $sourceDocument->type,
                'sort_order' => $sourceDocument->sort_order,
                'status' => $sourceDocument->status,
            ]);

            foreach ($sourceDocument->translations as $translation) {
                DocumentTranslation::create([
                    'document_id' => $newDocument->id,
                    'language_code' => $translation->language_code,
                    'title' => $translation->title,
                ]);
            }

            foreach ($sourceDocument->pages as $sourcePage) {
                $newPage = DocumentPage::create([
                    'document_id' => $newDocument->id,
                    'slug' => $sourcePage->slug,
                    'title' => $sourcePage->title,
                    'body_html' => $sourcePage->body_html,
                    'sort_order' => $sourcePage->sort_order,
                    'status' => $sourcePage->status,
                ]);

                foreach ($sourcePage->translations as $translation) {
                    DocumentPageTranslation::create([
                        'document_page_id' => $newPage->id,
                        'language_code' => $translation->language_code,
                        'title' => $translation->title,
                        'body_html' => $translation->body_html,
                    ]);
                }
            }
        }

        $laws = Law::query()
            ->where('edition_id', $sourceEdition->id)
            ->with([
                'translations',
                'contentNodes.translations',
                'contentNodes.mediaAssets',
                'qas.translations',
                'qas.options.translations',
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
                    'qa_type' => $sourceQa->qa_type,
                    'sort_order' => $sourceQa->sort_order,
                    'is_published' => $sourceQa->is_published,
                    'uses_custom_answer' => $sourceQa->uses_custom_answer,
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

                foreach ($sourceQa->options as $sourceOption) {
                    $newOption = LawQaOption::create([
                        'law_qa_id' => $newQa->id,
                        'sort_order' => $sourceOption->sort_order,
                        'is_correct' => $sourceOption->is_correct,
                    ]);

                    foreach ($sourceOption->translations as $translation) {
                        LawQaOptionTranslation::create([
                            'option_id' => $newOption->id,
                            'language_code' => $translation->language_code,
                            'text' => $translation->text,
                        ]);
                    }
                }
            }
        }
    }

    protected function makeCopiedLawSlug(string $sourceSlug, Edition $targetEdition): string
    {
        return UniqueSlugSuffixer::ensureUnique(
            Str::slug($sourceSlug),
            fn (string $candidate) => Law::query()
                ->where('edition_id', $targetEdition->id)
                ->where('slug', $candidate)
                ->exists()
        );
    }

    protected function makeCopiedDocumentSlug(string $sourceSlug, Edition $targetEdition): string
    {
        return UniqueSlugSuffixer::ensureUnique(
            Str::slug($sourceSlug),
            fn (string $candidate) => Document::query()
                ->where('edition_id', $targetEdition->id)
                ->where('slug', $candidate)
                ->exists()
        );
    }
}
