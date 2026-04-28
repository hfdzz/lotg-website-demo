<?php

namespace Tests\Feature;

use App\Models\ContentNode;
use App\Models\ContentNodeTranslation;
use App\Models\Edition;
use App\Models\Law;
use App\Models\LawTranslation;
use App\Models\Role;
use App\Models\User;
use App\Services\LawTreeBuilder;
use App\Services\LotgPublicCache;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContentCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_edition_cache_is_refreshed_after_admin_activation(): void
    {
        $this->actingAsSuperAdmin();

        $currentEdition = Edition::create([
            'name' => 'Edition 2025/26',
            'code' => 'edition-2025-26',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'published',
            'is_active' => true,
        ]);

        $nextEdition = Edition::create([
            'name' => 'Edition 2026/27',
            'code' => 'edition-2026-27',
            'year_start' => 2026,
            'year_end' => 2027,
            'status' => 'published',
            'is_active' => false,
        ]);

        $this->assertSame($currentEdition->id, Edition::current()?->id);

        $this->post(route('admin.editions.force-activate', $nextEdition))
            ->assertRedirect(route('admin.editions.index'));

        $this->assertSame($nextEdition->id, Edition::current()?->id);
    }

    public function test_law_listing_cache_is_refreshed_after_admin_law_creation(): void
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

        $lawOne = Law::create([
            'edition_id' => $edition->id,
            'law_number' => '1',
            'slug' => 'law-1',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        LawTranslation::create([
            'law_id' => $lawOne->id,
            'language_code' => 'id',
            'title' => 'Aturan Satu',
        ]);

        $publicCache = app(LotgPublicCache::class);

        $this->assertCount(1, $publicCache->orderedPublishedLaws($edition->id, ['translations']));

        $this->post(route('admin.laws.store', ['edition' => $edition]), [
            'law_number' => '2',
            'slug' => 'law-2',
            'sort_order' => 2,
            'status' => 'published',
            'title_id' => 'Aturan Dua',
            'subtitle_id' => null,
            'description_text_id' => null,
            'title_en' => 'Law Two',
            'subtitle_en' => null,
            'description_text_en' => null,
        ])->assertRedirect(route('admin.laws.index', ['edition' => $edition]));

        $this->assertCount(2, $publicCache->orderedPublishedLaws($edition->id, ['translations']));
    }

    public function test_law_tree_cache_is_refreshed_after_node_admin_update(): void
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

        $node = ContentNode::create([
            'law_id' => $law->id,
            'parent_id' => null,
            'node_type' => 'section',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        ContentNodeTranslation::create([
            'content_node_id' => $node->id,
            'language_code' => 'id',
            'title' => 'Judul Lama',
            'body_html' => '<p>Isi lama</p>',
            'status' => 'published',
        ]);

        ContentNodeTranslation::create([
            'content_node_id' => $node->id,
            'language_code' => 'en',
            'title' => 'Old Title',
            'body_html' => '<p>Old body</p>',
            'status' => 'published',
        ]);

        $treeBuilder = app(LawTreeBuilder::class);

        $this->assertSame('Judul Lama', $treeBuilder->build($law, 'id')[0]['title']);

        $this->patch(route('admin.nodes.update', ['edition' => $edition, 'law' => $law, 'node' => $node]), [
            'parent_id' => null,
            'node_type' => 'section',
            'sort_order' => 1,
            'title_id' => 'Judul Baru',
            'body_html_id' => '<p>Isi baru</p>',
            'translation_status_id' => 'published',
            'title_en' => 'New Title',
            'body_html_en' => '<p>New body</p>',
            'translation_status_en' => 'published',
            'is_published' => '1',
        ])->assertRedirect(route('admin.nodes.edit', ['edition' => $edition, 'law' => $law, 'node' => $node]));

        $this->assertSame('Judul Baru', $treeBuilder->build($law->fresh(), 'id')[0]['title']);
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
