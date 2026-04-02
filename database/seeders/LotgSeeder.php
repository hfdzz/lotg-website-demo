<?php

namespace Database\Seeders;

use App\Models\ChangelogEntry;
use App\Models\ContentNode;
use App\Models\ContentNodeTranslation;
use App\Models\Law;
use App\Models\LawTranslation;
use App\Models\MediaAsset;
use App\Support\LotgLanguage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LotgSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $law = Law::updateOrCreate(
                ['slug' => 'law-1-the-field-of-play'],
                [
                    'law_number' => '1',
                    'sort_order' => 1,
                    'status' => 'published',
                ]
            );

            $this->syncLawTranslations($law, [
                'id' => [
                    'title' => 'Lapangan Permainan',
                    'subtitle' => 'Persyaratan dasar lapangan dan penandaan',
                    'description_text' => 'Ringkasan hukum ini mencakup prinsip umum, ukuran lapangan, penandaan, serta contoh visual untuk membantu pembacaan publik dan pengujian alur admin.',
                ],
                'en' => [
                    'title' => 'The Field of Play',
                    'subtitle' => 'Core requirements for the playing surface and markings',
                    'description_text' => 'This law summary covers the general principles, field dimensions, markings, and visual examples used to validate both the public reading experience and the admin workflow.',
                ],
            ]);

            $law->contentNodes()->delete();

            $sectionOverview = $this->makeNode($law->id, null, 'section', 1, 'Overview', null);
            $this->makeNode(
                $law->id,
                $sectionOverview->id,
                'rich_text',
                1,
                null,
                '<p>The field of play must be safe, clearly marked, and suitable for the match. This sample content shows how a law can combine structured headings with editor-managed HTML.</p>'
            );

            $sectionDimensions = $this->makeNode($law->id, null, 'section', 2, 'Field dimensions', null);
            $subsectionMarkings = $this->makeNode($law->id, $sectionDimensions->id, 'section', 1, 'Field markings', null);
            $this->makeNode(
                $law->id,
                $subsectionMarkings->id,
                'rich_text',
                1,
                null,
                '<p>Boundary lines belong to the areas they define. All lines must be of the same width and clearly visible to players, officials, and spectators.</p>'
            );

            $imageAsset = MediaAsset::updateOrCreate(
                ['file_path' => 'demo/field-layout.svg'],
                [
                    'asset_type' => 'image',
                    'storage_type' => 'upload',
                    'caption' => 'Sample field layout illustration.',
                    'credit' => 'LotG demo asset',
                ]
            );

            $imageNode = $this->makeNode($law->id, $sectionDimensions->id, 'image', 2, 'Reference diagram', null);
            $imageNode->mediaAssets()->sync([
                $imageAsset->id => ['sort_order' => 1],
            ]);

            $videoNode = $this->makeNode($law->id, null, 'video_group', 3, 'Video examples', null, [
                'layout' => 'stacked',
            ]);

            $videoAssets = [
                MediaAsset::updateOrCreate(
                    ['external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
                    [
                        'asset_type' => 'video',
                        'storage_type' => 'youtube',
                        'caption' => 'Example video clip 1.',
                    ]
                ),
                MediaAsset::updateOrCreate(
                    ['external_url' => 'https://www.youtube.com/watch?v=9bZkp7q19f0'],
                    [
                        'asset_type' => 'video',
                        'storage_type' => 'youtube',
                        'caption' => 'Example video clip 2.',
                    ]
                ),
            ];

            $videoNode->mediaAssets()->sync([
                $videoAssets[0]->id => ['sort_order' => 1],
                $videoAssets[1]->id => ['sort_order' => 2],
            ]);

            ChangelogEntry::updateOrCreate(
                [
                    'language_code' => 'id',
                    'title' => 'Initial LotG sample structure',
                ],
                [
                    'body' => 'Seeded two public law examples with nested sections, richer text, image media, and YouTube video embeds for admin workflow testing.',
                    'sort_order' => 1,
                    'published_at' => now(),
                ]
            );

            ChangelogEntry::updateOrCreate(
                [
                    'language_code' => 'en',
                    'title' => 'Initial LotG sample structure',
                ],
                [
                    'body' => 'Seeded two public law examples with nested sections, richer text, image media, and YouTube video embeds for admin workflow testing.',
                    'sort_order' => 1,
                    'published_at' => now(),
                ]
            );

            $this->seedLawTwo();
            $this->seedLawThree();
        });
    }

    protected function makeNode(
        int $lawId,
        ?int $parentId,
        string $nodeType,
        int $sortOrder,
        string|array|null $title,
        string|array|null $bodyHtml,
        ?array $settings = null
    ): ContentNode {
        $node = ContentNode::create([
            'law_id' => $lawId,
            'parent_id' => $parentId,
            'node_type' => $nodeType,
            'sort_order' => $sortOrder,
            'is_published' => true,
            'settings_json' => $settings,
        ]);

        foreach (array_keys(LotgLanguage::supported()) as $languageCode) {
            ContentNodeTranslation::create([
                'content_node_id' => $node->id,
                'language_code' => $languageCode,
                'title' => is_array($title) ? ($title[$languageCode] ?? $title['id'] ?? $title['en'] ?? null) : $title,
                'body_html' => is_array($bodyHtml) ? ($bodyHtml[$languageCode] ?? $bodyHtml['id'] ?? $bodyHtml['en'] ?? null) : $bodyHtml,
                'status' => 'published',
            ]);
        }

        return $node;
    }

    protected function syncLawTranslations(Law $law, array $translations): void
    {
        foreach (array_keys(LotgLanguage::supported()) as $languageCode) {
            $payload = $translations[$languageCode] ?? $translations['id'] ?? $translations['en'] ?? null;

            if (! $payload || empty($payload['title'])) {
                continue;
            }

            LawTranslation::updateOrCreate(
                [
                    'law_id' => $law->id,
                    'language_code' => $languageCode,
                ],
                [
                    'title' => $payload['title'],
                    'subtitle' => $payload['subtitle'] ?? null,
                    'description_text' => $payload['description_text'] ?? null,
                ]
            );
        }
    }

    protected function seedLawTwo(): void
    {
        $law = Law::updateOrCreate(
            ['slug' => 'law-2-the-ball'],
            [
                'law_number' => '2',
                'sort_order' => 2,
                'status' => 'published',
            ]
        );

        $this->syncLawTranslations($law, [
            'id' => [
                'title' => 'Bola',
                'subtitle' => 'Standar teknis, pemeriksaan, dan penggantian',
                'description_text' => 'Contoh hukum ini memuat uraian yang lebih panjang, beberapa bagian utama, subbagian bertingkat, serta contoh gambar dan video untuk menguji kenyamanan membaca dan alur input admin.',
            ],
            'en' => [
                'title' => 'The Ball',
                'subtitle' => 'Technical standards, inspection, and replacement',
                'description_text' => 'This example law includes longer copy, multiple sections, nested subsections, and supporting image and video examples so the reading layout and editorial workflow can be tested more realistically.',
            ],
        ]);

        $law->contentNodes()->delete();

        $sectionStandards = $this->makeNode($law->id, null, 'section', 1, 'Standards and approval', null);
        $this->makeNode(
            $law->id,
            $sectionStandards->id,
            'rich_text',
            1,
            null,
            '<p>The ball used in a match must meet the required size, weight, and pressure standards. Competition organizers should also ensure that replacement balls are checked before kickoff and remain available throughout the match.</p><p>For this MVP, longer text helps us validate reading comfort, spacing, and the admin flow for paragraph-heavy sections.</p>'
        );

        $sectionSpecs = $this->makeNode($law->id, null, 'section', 2, 'Technical specifications', null);
        $subsectionSize = $this->makeNode($law->id, $sectionSpecs->id, 'section', 1, 'Size and circumference', null);
        $this->makeNode(
            $law->id,
            $subsectionSize->id,
            'rich_text',
            1,
            null,
            '<p>A match ball should remain within the approved circumference range and must feel consistent in flight, bounce, and control. Officials should be able to compare match balls quickly when concerns are raised.</p>'
        );

        $subsectionPressure = $this->makeNode($law->id, $sectionSpecs->id, 'section', 2, 'Pressure and performance', null);
        $this->makeNode(
            $law->id,
            $subsectionPressure->id,
            'rich_text',
            1,
            null,
            '<p>Pressure should be checked before play and again whenever the ball is changed. A ball that feels soft, unstable, or irregular can directly affect fairness and should be replaced promptly.</p><ul><li>Check pressure before kickoff.</li><li>Replace damaged balls without unnecessary delay.</li><li>Keep spare approved balls ready near the field of play.</li></ul>'
        );

        $sectionVisualGuidance = $this->makeNode($law->id, null, 'section', 3, 'Visual guidance', null);
        $this->makeNode(
            $law->id,
            $sectionVisualGuidance->id,
            'rich_text',
            1,
            null,
            '<p>This section includes a reference image and video examples to test mixed media layout and admin entry. It also helps validate how support material sits next to explanatory content.</p>'
        );

        $ballImage = MediaAsset::updateOrCreate(
            ['file_path' => 'demo/ball-spec.svg'],
            [
                'asset_type' => 'image',
                'storage_type' => 'upload',
                'caption' => 'Sample illustration of approved ball markings and measurement zones.',
                'credit' => 'LotG demo asset',
            ]
        );

        $imageNode = $this->makeNode($law->id, $sectionVisualGuidance->id, 'image', 2, 'Reference ball diagram', null);
        $imageNode->mediaAssets()->sync([
            $ballImage->id => ['sort_order' => 1],
        ]);

        $videoNode = $this->makeNode($law->id, $sectionVisualGuidance->id, 'video_group', 3, 'Inspection clips', null, [
            'layout' => 'stacked',
        ]);

        $videoAssets = [
            MediaAsset::updateOrCreate(
                ['external_url' => 'https://www.youtube.com/watch?v=ysz5S6PUM-U'],
                [
                    'asset_type' => 'video',
                    'storage_type' => 'youtube',
                    'caption' => 'Example inspection clip 1.',
                ]
            ),
            MediaAsset::updateOrCreate(
                ['external_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ'],
                [
                    'asset_type' => 'video',
                    'storage_type' => 'youtube',
                    'caption' => 'Example inspection clip 2.',
                ]
            ),
        ];

        $videoNode->mediaAssets()->sync([
            $videoAssets[0]->id => ['sort_order' => 1],
            $videoAssets[1]->id => ['sort_order' => 2],
        ]);

        $sectionReplacement = $this->makeNode($law->id, null, 'section', 4, 'Replacement procedure', null);
        $childReasons = $this->makeNode($law->id, $sectionReplacement->id, 'section', 1, 'Reasons for replacement', null);
        $this->makeNode(
            $law->id,
            $childReasons->id,
            'rich_text',
            1,
            null,
            '<p>A ball may need to be replaced because of damage, loss of pressure, or unsafe surface wear. Match control improves when replacements happen consistently and are communicated clearly.</p>'
        );

        $childRestarts = $this->makeNode($law->id, $sectionReplacement->id, 'section', 2, 'Restart considerations', null);
        $this->makeNode(
            $law->id,
            $childRestarts->id,
            'rich_text',
            1,
            null,
            '<p>When the ball is replaced, the restart should follow the relevant law and match situation. This subsection intentionally has text only, with no media, to test section spacing in a deeper branch.</p>'
        );

        $sectionCompetitionNotes = $this->makeNode($law->id, null, 'section', 5, 'Competition notes', null);
        $this->makeNode($law->id, $sectionCompetitionNotes->id, 'section', 1, 'Youth competitions', null);
        $this->makeNode($law->id, $sectionCompetitionNotes->id, 'section', 2, 'Training adaptations', null);
    }

    protected function seedLawThree(): void
    {
        $law = Law::updateOrCreate(
            ['slug' => 'law-3-the-players'],
            [
                'law_number' => '3',
                'sort_order' => 3,
                'status' => 'published',
            ]
        );

        $this->syncLawTranslations($law, [
            'id' => [
                'title' => 'Pemain',
                'subtitle' => 'Prinsip dasar, administrasi pertandingan, dan referensi',
                'description_text' => 'Contoh ini dirancang sebagai hukum yang lebih lengkap, dengan teks panjang, media tertanam, dan daftar sumber tertaut agar seluruh fitur utama sistem dapat divalidasi dalam satu halaman.',
            ],
            'en' => [
                'title' => 'The Players',
                'subtitle' => 'Core principles, match administration, and references',
                'description_text' => 'This is a fuller seeded example with longer narrative content, embedded media, and linked resources so the main public and admin features can be validated on a single law page.',
            ],
        ]);

        $law->contentNodes()->delete();

        $sectionPrinciples = $this->makeNode($law->id, null, 'section', 1, 'Core principles', null);
        $this->makeNode(
            $law->id,
            $sectionPrinciples->id,
            'rich_text',
            1,
            null,
            '<p>Each match is played by two teams, and each team should begin with the required number of players under the competition rules. The purpose of this seeded law is to exercise the full structured publishing model with realistic long-form content, nested sections, embedded media, and linked resources on the same page.</p><p>In practice, administrators often need to mix explanatory guidance, supporting diagrams, official circulars, and training references. This example is intentionally more complete so you can validate reading comfort, editorial rhythm, and admin workflow without hunting across multiple sample laws.</p>'
        );

        $sectionEligibility = $this->makeNode($law->id, null, 'section', 2, 'Eligibility and team sheet', null);
        $subsectionStarting = $this->makeNode($law->id, $sectionEligibility->id, 'section', 1, 'Starting players', null);
        $this->makeNode(
            $law->id,
            $subsectionStarting->id,
            'rich_text',
            1,
            null,
            '<p>Only players listed and approved before kickoff may start the match. Match officials should be able to confirm player identity, shirt number, and any competition-specific eligibility requirements quickly and consistently.</p><p>Where competitions require digital team sheets, staff should still confirm that the submitted list matches the players physically present in the technical area. Clear record-keeping reduces avoidable disputes and makes later disciplinary review easier.</p>'
        );

        $subsectionSubstitutes = $this->makeNode($law->id, $sectionEligibility->id, 'section', 2, 'Substitutes and substituted players', null);
        $this->makeNode(
            $law->id,
            $subsectionSubstitutes->id,
            'rich_text',
            1,
            null,
            '<p>Substitutes should be clearly identified and remain subject to competition rules on participation, re-entry, and conduct. A clean content structure is especially useful here because the public page may need to combine broad legal wording with local implementation guidance, referee reminders, and reference material.</p><ul><li>List substitutes before kickoff.</li><li>Record every substitution event accurately.</li><li>Keep technical-area procedures consistent across matches.</li></ul>'
        );

        $sectionPracticalAdministration = $this->makeNode($law->id, null, 'section', 3, 'Practical administration', null);
        $subsectionChecks = $this->makeNode($law->id, $sectionPracticalAdministration->id, 'section', 1, 'Pre-match checks', null);
        $this->makeNode(
            $law->id,
            $subsectionChecks->id,
            'rich_text',
            1,
            null,
            '<p>Before kickoff, officials and competition staff should verify player equipment, team sheets, benches, and communication channels. This section has no media attached so the page still contains plain reading stretches between richer blocks.</p>'
        );

        $subsectionBench = $this->makeNode($law->id, $sectionPracticalAdministration->id, 'section', 2, 'Bench organization', null);
        $this->makeNode(
            $law->id,
            $subsectionBench->id,
            'rich_text',
            1,
            null,
            '<p>Good bench organization supports smoother substitution management and clearer control of support staff. Competitions may supplement the law with local competition notes on accreditation, seating zones, and document handling, which makes linked resource lists particularly useful.</p>'
        );

        $sectionVisualExamples = $this->makeNode($law->id, null, 'section', 4, 'Visual examples', null);
        $this->makeNode(
            $law->id,
            $sectionVisualExamples->id,
            'rich_text',
            1,
            null,
            '<p>This section combines a diagram and embedded video examples to validate the page layout under heavier editorial use. It should help you review whether spacing remains comfortable when text, images, and video all appear within the same law.</p>'
        );

        $fieldImage = MediaAsset::updateOrCreate(
            ['file_path' => 'demo/field-layout.svg'],
            [
                'asset_type' => 'image',
                'storage_type' => 'upload',
                'caption' => 'Illustrative layout showing player entry and bench-side reference areas.',
                'credit' => 'LotG demo asset',
            ]
        );

        $imageNode = $this->makeNode($law->id, $sectionVisualExamples->id, 'image', 2, 'Player area reference diagram', null);
        $imageNode->mediaAssets()->sync([
            $fieldImage->id => ['sort_order' => 1],
        ]);

        $videoNode = $this->makeNode($law->id, $sectionVisualExamples->id, 'video_group', 3, 'Match operations clips', null, [
            'layout' => 'stacked',
        ]);

        $videoAssets = [
            MediaAsset::updateOrCreate(
                ['external_url' => 'https://www.youtube.com/watch?v=ysz5S6PUM-U'],
                [
                    'asset_type' => 'video',
                    'storage_type' => 'youtube',
                    'caption' => 'Arrival and verification example.',
                ]
            ),
            MediaAsset::updateOrCreate(
                ['external_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ'],
                [
                    'asset_type' => 'video',
                    'storage_type' => 'youtube',
                    'caption' => 'Substitution workflow example.',
                ]
            ),
        ];

        $videoNode->mediaAssets()->sync([
            $videoAssets[0]->id => ['sort_order' => 1],
            $videoAssets[1]->id => ['sort_order' => 2],
        ]);

        $sectionReferences = $this->makeNode($law->id, null, 'section', 5, 'Reference materials', null);
        $this->makeNode(
            $law->id,
            $sectionReferences->id,
            'rich_text',
            1,
            null,
            '<p>Some supporting material works better as linked references than embedded content. This lets editors attach official circulars, downloadable forms, or linked video references without forcing everything into the reading flow.</p>'
        );

        $resourceNode = $this->makeNode($law->id, $sectionReferences->id, 'resource_list', 2, 'Useful resources', null, [
            'layout' => 'list',
        ]);

        $resourceAssets = [
            MediaAsset::create([
                'asset_type' => 'document',
                'storage_type' => 'external',
                'external_url' => 'https://example.com/documents/team-sheet-guidance.pdf',
                'caption' => 'Team sheet guidance PDF',
            ]),
            MediaAsset::create([
                'asset_type' => 'external_link',
                'storage_type' => 'external',
                'external_url' => 'https://example.com/competition/player-management',
                'caption' => 'Competition player management page',
            ]),
            MediaAsset::create([
                'asset_type' => 'video_link',
                'storage_type' => 'external',
                'external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'caption' => 'Linked training clip',
            ]),
            MediaAsset::updateOrCreate(
                ['file_path' => 'demo/logo_pssi_tulisan.png'],
                [
                    'asset_type' => 'file',
                    'storage_type' => 'upload',
                    'caption' => 'Sample downloadable federation asset',
                ]
            ),
        ];

        $resourceNode->mediaAssets()->sync([
            $resourceAssets[0]->id => ['sort_order' => 1],
            $resourceAssets[1]->id => ['sort_order' => 2],
            $resourceAssets[2]->id => ['sort_order' => 3],
            $resourceAssets[3]->id => ['sort_order' => 4],
        ]);

        $sectionStructureOnly = $this->makeNode($law->id, null, 'section', 6, 'Special competition structure', null);
        $this->makeNode($law->id, $sectionStructureOnly->id, 'section', 1, 'Youth match exceptions', null);
        $this->makeNode($law->id, $sectionStructureOnly->id, 'section', 2, 'Tournament reporting flow', null);
        $this->makeNode($law->id, $sectionStructureOnly->id, 'section', 3, 'Post-match administration', null);
    }
}
