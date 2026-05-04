<?php

namespace Tests\Feature;

use App\Models\ContentNode;
use App\Models\ContentNodeTranslation;
use App\Models\Edition;
use App\Models\Law;
use App\Models\LawTranslation;
use App\Models\MediaAsset;
use App\Services\LawTreeBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HostedVideoRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_video_media_is_built_as_a_hosted_video_item(): void
    {
        Storage::fake('public');

        $edition = Edition::create([
            'name' => 'Rendered Edition',
            'code' => 'rendered-edition',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'published',
            'is_active' => true,
        ]);

        $law = Law::create([
            'edition_id' => $edition->id,
            'law_number' => '3',
            'slug' => 'the-players',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        LawTranslation::create([
            'law_id' => $law->id,
            'language_code' => 'id',
            'title' => 'Para Pemain',
        ]);

        $videoNode = ContentNode::create([
            'law_id' => $law->id,
            'parent_id' => null,
            'node_type' => 'video_group',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        ContentNodeTranslation::create([
            'content_node_id' => $videoNode->id,
            'language_code' => 'id',
            'title' => 'Video Penjelasan',
            'status' => 'published',
        ]);

        Storage::disk('public')->put('lotg-media/videos/players.mp4', 'fake-video-content');

        $videoAsset = MediaAsset::create([
            'asset_type' => 'video',
            'storage_type' => 'upload',
            'storage_disk' => 'public',
            'is_library_item' => true,
            'file_path' => 'lotg-media/videos/players.mp4',
            'caption' => 'Hosted players video',
        ]);

        $videoNode->mediaAssets()->sync([
            $videoAsset->id => ['sort_order' => 1],
        ]);

        $tree = app(LawTreeBuilder::class)->build($law, 'id');

        $this->assertCount(1, $tree);
        $this->assertSame('video_group', $tree[0]['node_type']);
        $this->assertCount(1, $tree[0]['media_items']);
        $this->assertSame('video', $tree[0]['media_items'][0]['kind']);
        $this->assertSame('file', $tree[0]['media_items'][0]['player']);
        $this->assertFalse($tree[0]['media_items'][0]['defer_src']);
        $this->assertStringContainsString('/storage/lotg-media/videos/players.mp4', $tree[0]['media_items'][0]['src']);
    }
}
