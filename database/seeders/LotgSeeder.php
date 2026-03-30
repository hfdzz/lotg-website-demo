<?php

namespace Database\Seeders;

use App\Models\ChangelogEntry;
use App\Models\ContentNode;
use App\Models\ContentNodeTranslation;
use App\Models\Law;
use App\Models\MediaAsset;
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
        });
    }

    protected function makeNode(
        int $lawId,
        ?int $parentId,
        string $nodeType,
        int $sortOrder,
        ?string $title,
        ?string $bodyHtml,
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

        ContentNodeTranslation::create([
            'content_node_id' => $node->id,
            'language_code' => 'en',
            'title' => $title,
            'body_html' => $bodyHtml,
            'status' => 'published',
        ]);

        return $node;
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
}
