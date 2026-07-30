<?php

namespace Tests\Feature;

use App\Models\ContentNode;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\Edition;
use App\Models\Law;
use App\Models\MediaAsset;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MediaAdminUsageSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_admin_shows_node_and_published_node_usage_counts(): void
    {
        $this->actingAsSuperAdmin();

        $edition = Edition::create([
            'name' => 'Edition 2025/26',
            'code' => 'edition-2025-26',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'published',
            'is_active' => true,
        ]);

        $law = Law::create([
            'edition_id' => $edition->id,
            'law_number' => '1',
            'slug' => 'law-1',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        $publishedNode = ContentNode::create([
            'law_id' => $law->id,
            'parent_id' => null,
            'node_type' => 'image',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $draftNode = ContentNode::create([
            'law_id' => $law->id,
            'parent_id' => null,
            'node_type' => 'image',
            'sort_order' => 2,
            'is_published' => false,
        ]);

        $document = Document::create([
            'edition_id' => $edition->id,
            'slug' => 'var-protocol',
            'title' => 'VAR Protocol',
            'type' => 'single',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        $page = DocumentPage::create([
            'document_id' => $document->id,
            'slug' => 'overview',
            'title' => 'Overview',
            'body_html' => '<p>Overview</p>',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        $media = MediaAsset::create([
            'asset_type' => 'image',
            'storage_type' => 'upload',
            'storage_disk' => 'public',
            'is_library_item' => true,
            'file_path' => 'lotg-media/images/example.png',
            'caption' => 'Example image',
            'credit' => 'IFAB',
        ]);

        MediaAsset::withoutTimestamps(function () use ($media): void {
            MediaAsset::query()
                ->whereKey($media->id)
                ->update([
                    'created_at' => Carbon::parse('2026-07-21 10:00:00'),
                    'updated_at' => Carbon::parse('2026-07-21 12:15:00'),
                ]);
        });

        $media->refresh();

        $publishedNode->mediaAssets()->sync([$media->id => ['sort_order' => 1]]);
        $draftNode->mediaAssets()->sync([$media->id => ['sort_order' => 1]]);
        $page->mediaAssets()->sync([$media->id => ['media_key' => 'example-image', 'sort_order' => 1]]);

        $this->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('Used in 2 nodes, 1 published')
            ->assertSee('Used in active edition')
            ->assertSee('Also used in 1 document page')
            ->assertSee('Added 21 Jul 2026 10:00 (updated 21 Jul 2026 12:15)');

        $this->get(route('admin.media.edit', ['media' => $media]))
            ->assertOk()
            ->assertSee('Usage: 2 nodes, 1 published')
            ->assertSee('Used in active edition')
            ->assertSee('Document pages: 1')
            ->assertSee('Added 21 Jul 2026 10:00 (updated 21 Jul 2026 12:15)');
    }

    public function test_media_admin_can_filter_by_media_type(): void
    {
        $this->actingAsSuperAdmin();

        MediaAsset::create([
            'asset_type' => 'image',
            'storage_type' => 'upload',
            'storage_disk' => 'public',
            'is_library_item' => true,
            'file_path' => 'lotg-media/images/filter-image.png',
            'caption' => 'Filter image',
            'credit' => 'IFAB',
        ]);

        MediaAsset::create([
            'asset_type' => 'video',
            'storage_type' => 'youtube',
            'storage_disk' => null,
            'is_library_item' => true,
            'file_path' => null,
            'external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'caption' => 'Filter video',
            'credit' => 'YouTube',
        ]);

        $this->get(route('admin.media.index', ['media_type' => 'image']))
            ->assertOk()
            ->assertSee('Filter image')
            ->assertDontSee('Filter video');
    }

    public function test_media_admin_can_filter_by_usage_state(): void
    {
        $this->actingAsSuperAdmin();

        $activeEdition = Edition::create([
            'name' => 'Edition 2025/26',
            'code' => 'edition-2025-26',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'published',
            'is_active' => true,
        ]);

        $inactiveEdition = Edition::create([
            'name' => 'Edition 2024/25',
            'code' => 'edition-2024-25',
            'year_start' => 2024,
            'year_end' => 2025,
            'status' => 'published',
            'is_active' => false,
        ]);

        $activeLaw = Law::create([
            'edition_id' => $activeEdition->id,
            'law_number' => '1',
            'slug' => 'active-law-1',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        $inactiveLaw = Law::create([
            'edition_id' => $inactiveEdition->id,
            'law_number' => '2',
            'slug' => 'inactive-law-2',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        $publishedActiveNode = ContentNode::create([
            'law_id' => $activeLaw->id,
            'parent_id' => null,
            'node_type' => 'image',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $draftInactiveNode = ContentNode::create([
            'law_id' => $inactiveLaw->id,
            'parent_id' => null,
            'node_type' => 'image',
            'sort_order' => 1,
            'is_published' => false,
        ]);

        $document = Document::create([
            'edition_id' => $activeEdition->id,
            'slug' => 'usage-doc',
            'title' => 'Usage Document',
            'type' => 'single',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        $page = DocumentPage::create([
            'document_id' => $document->id,
            'slug' => 'usage-overview',
            'title' => 'Overview',
            'body_html' => '<p>Overview</p>',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        $activeMedia = MediaAsset::create([
            'asset_type' => 'image',
            'storage_type' => 'upload',
            'storage_disk' => 'public',
            'is_library_item' => true,
            'file_path' => 'lotg-media/images/active-filter-image.png',
            'caption' => 'Active media',
            'credit' => 'IFAB',
        ]);

        $draftMedia = MediaAsset::create([
            'asset_type' => 'image',
            'storage_type' => 'upload',
            'storage_disk' => 'public',
            'is_library_item' => true,
            'file_path' => 'lotg-media/images/draft-filter-image.png',
            'caption' => 'Draft media',
            'credit' => 'IFAB',
        ]);

        $documentOnlyMedia = MediaAsset::create([
            'asset_type' => 'image',
            'storage_type' => 'upload',
            'storage_disk' => 'public',
            'is_library_item' => true,
            'file_path' => 'lotg-media/images/document-filter-image.png',
            'caption' => 'Document-only media',
            'credit' => 'IFAB',
        ]);

        $unusedMedia = MediaAsset::create([
            'asset_type' => 'image',
            'storage_type' => 'upload',
            'storage_disk' => 'public',
            'is_library_item' => true,
            'file_path' => 'lotg-media/images/unused-filter-image.png',
            'caption' => 'Unused media',
            'credit' => 'IFAB',
        ]);

        $publishedActiveNode->mediaAssets()->sync([$activeMedia->id => ['sort_order' => 1]]);
        $draftInactiveNode->mediaAssets()->sync([$draftMedia->id => ['sort_order' => 1]]);
        $page->mediaAssets()->sync([$documentOnlyMedia->id => ['media_key' => 'document-only-media', 'sort_order' => 1]]);

        $this->get(route('admin.media.index', ['usage_filter' => 'any']))
            ->assertOk()
            ->assertSee('Active media')
            ->assertSee('Draft media')
            ->assertSee('Document-only media')
            ->assertDontSee('Unused media');

        $this->get(route('admin.media.index', ['usage_filter' => 'published']))
            ->assertOk()
            ->assertSee('Active media')
            ->assertDontSee('Draft media')
            ->assertDontSee('Document-only media')
            ->assertDontSee('Unused media');

        $this->get(route('admin.media.index', ['usage_filter' => 'active']))
            ->assertOk()
            ->assertSee('Active media')
            ->assertDontSee('Draft media')
            ->assertDontSee('Document-only media')
            ->assertDontSee('Unused media');

        $this->get(route('admin.media.index', ['usage_filter' => 'no']))
            ->assertOk()
            ->assertDontSee('Active media')
            ->assertDontSee('Draft media')
            ->assertDontSee('Document-only media')
            ->assertSee('Unused media');
    }

    protected function actingAsSuperAdmin(): User
    {
        $this->seed(RbacSeeder::class);

        $user = User::factory()->create();
        $role = Role::query()->where('code', Role::SUPER_ADMIN)->firstOrFail();
        $user->roles()->attach($role);

        $this->actingAs($user);

        return $user;
    }
}
