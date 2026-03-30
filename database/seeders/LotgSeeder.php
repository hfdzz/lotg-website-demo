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
                    'body' => 'Seeded the first public law with nested sections, rich text, image media, and YouTube video embeds.',
                    'sort_order' => 1,
                    'published_at' => now(),
                ]
            );
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
}
