<?php

namespace Tests\Feature;

use App\Models\ContentNode;
use App\Models\Edition;
use App\Models\Law;
use App\Models\LawTranslation;
use App\Models\MediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaPruneCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_prune_dry_run_reports_orphaned_media_without_deleting(): void
    {
        Storage::fake('public');

        $orphan = $this->createOrphanedImageAsset();
        $this->createReferencedImageAsset();

        $this->artisan('lotg:media-prune', ['--dry-run' => true])
            ->expectsOutputToContain('Media prune dry run summary.')
            ->expectsOutputToContain('Orphaned media assets: 1')
            ->expectsOutputToContain('#'.$orphan->id.' [image] public: lotg-media/images/orphaned-image.png')
            ->expectsOutputToContain('Dry run passed. No media was deleted.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('media_assets', ['id' => $orphan->id]);
        $this->assertTrue(Storage::disk('public')->exists('lotg-media/images/orphaned-image.png'));
    }

    public function test_media_prune_deletes_orphaned_media_and_preserves_referenced_media(): void
    {
        Storage::fake('public');

        $orphan = $this->createOrphanedImageAsset();
        $referenced = $this->createReferencedImageAsset();

        $this->artisan('lotg:media-prune')
            ->expectsOutputToContain('Media prune summary.')
            ->expectsOutputToContain('Orphaned media assets: 1')
            ->expectsOutputToContain('Deleted orphaned media assets: 1')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('media_assets', ['id' => $orphan->id]);
        $this->assertDatabaseHas('media_assets', ['id' => $referenced->id]);
        $this->assertTrue(Storage::disk('public')->exists('lotg-media/images/referenced-image.png'));
    }

    protected function createOrphanedImageAsset(): MediaAsset
    {
        Storage::disk('public')->put('lotg-media/images/orphaned-image.png', 'orphaned-image-content');

        return MediaAsset::create([
            'asset_type' => 'image',
            'storage_type' => 'upload',
            'storage_disk' => 'public',
            'is_library_item' => true,
            'file_path' => 'lotg-media/images/orphaned-image.png',
            'caption' => 'Orphaned image',
        ]);
    }

    protected function createReferencedImageAsset(): MediaAsset
    {
        $edition = Edition::create([
            'name' => 'Media Prune Edition',
            'code' => 'media-prune-edition',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'published',
            'is_active' => true,
        ]);

        $law = Law::create([
            'edition_id' => $edition->id,
            'law_number' => '6',
            'slug' => 'other-match-officials',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        LawTranslation::create([
            'law_id' => $law->id,
            'language_code' => 'id',
            'title' => 'Ofisial Pertandingan Lainnya',
        ]);

        $node = ContentNode::create([
            'law_id' => $law->id,
            'parent_id' => null,
            'node_type' => 'image',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Storage::disk('public')->put('lotg-media/images/referenced-image.png', 'referenced-image-content');

        $asset = MediaAsset::create([
            'asset_type' => 'image',
            'storage_type' => 'upload',
            'storage_disk' => 'public',
            'is_library_item' => true,
            'file_path' => 'lotg-media/images/referenced-image.png',
            'caption' => 'Referenced image',
        ]);

        $node->mediaAssets()->sync([
            $asset->id => ['sort_order' => 1],
        ]);

        return $asset;
    }
}
