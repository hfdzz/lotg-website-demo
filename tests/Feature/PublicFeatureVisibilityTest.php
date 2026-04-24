<?php

namespace Tests\Feature;

use App\Models\ChangelogEntry;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\DocumentPageTranslation;
use App\Models\DocumentTranslation;
use App\Models\Edition;
use App\Models\FeatureVisibility;
use App\Models\Role;
use App\Models\User;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\LawController;
use App\Services\LotgFeatureVisibility;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Tests\TestCase;

class PublicFeatureVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_global_and_edition_feature_visibility(): void
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

        $this->patch(route('admin.public-features.update'), [
            'edition' => $edition->id,
            'features' => [
                LotgFeatureVisibility::FEATURE_DOCUMENTS => 'disabled',
                LotgFeatureVisibility::FEATURE_QAS => 'enabled',
                LotgFeatureVisibility::FEATURE_LEGACY_UPDATES => 'default',
            ],
        ])->assertRedirect(route('admin.public-features.index', ['edition' => $edition->id]));

        $this->assertDatabaseHas('feature_visibilities', [
            'feature_key' => LotgFeatureVisibility::FEATURE_DOCUMENTS,
            'scope_type' => FeatureVisibility::SCOPE_GLOBAL,
            'edition_id' => null,
            'is_enabled' => false,
        ]);

        $this->assertDatabaseHas('feature_visibilities', [
            'feature_key' => LotgFeatureVisibility::FEATURE_QAS,
            'scope_type' => FeatureVisibility::SCOPE_GLOBAL,
            'edition_id' => null,
            'is_enabled' => true,
        ]);

        $this->assertDatabaseMissing('feature_visibilities', [
            'feature_key' => LotgFeatureVisibility::FEATURE_LEGACY_UPDATES,
            'scope_type' => FeatureVisibility::SCOPE_GLOBAL,
        ]);

        $this->patch(route('admin.editions.public-features.update-edition', $edition), [
            'features' => [
                LotgFeatureVisibility::FEATURE_DOCUMENTS => 'inherit',
                LotgFeatureVisibility::FEATURE_QAS => 'disabled',
                LotgFeatureVisibility::FEATURE_LEGACY_UPDATES => 'enabled',
            ],
        ])->assertRedirect(route('admin.editions.index', ['edition' => $edition->id]));

        $this->assertDatabaseMissing('feature_visibilities', [
            'feature_key' => LotgFeatureVisibility::FEATURE_DOCUMENTS,
            'scope_type' => FeatureVisibility::SCOPE_EDITION,
            'edition_id' => $edition->id,
        ]);

        $this->assertDatabaseHas('feature_visibilities', [
            'feature_key' => LotgFeatureVisibility::FEATURE_QAS,
            'scope_type' => FeatureVisibility::SCOPE_EDITION,
            'edition_id' => $edition->id,
            'is_enabled' => false,
        ]);

        $this->assertDatabaseHas('feature_visibilities', [
            'feature_key' => LotgFeatureVisibility::FEATURE_LEGACY_UPDATES,
            'scope_type' => FeatureVisibility::SCOPE_EDITION,
            'edition_id' => $edition->id,
            'is_enabled' => true,
        ]);
    }

    public function test_qas_link_is_hidden_and_route_redirects_when_disabled_for_the_active_edition(): void
    {
        $activeEdition = Edition::create([
            'name' => 'Edition 2025/26',
            'code' => 'edition-2025-26',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'published',
            'is_active' => true,
        ]);

        FeatureVisibility::create([
            'feature_key' => LotgFeatureVisibility::FEATURE_QAS,
            'scope_type' => FeatureVisibility::SCOPE_EDITION,
            'edition_id' => $activeEdition->id,
            'is_enabled' => false,
        ]);

        $this->assertFalse(app(LotgFeatureVisibility::class)->enabled(LotgFeatureVisibility::FEATURE_QAS, $activeEdition));

        $this->get(route('qas.index', ['lang' => 'en']))
            ->assertRedirect(route('laws.index', ['lang' => 'en']));
    }

    public function test_documents_are_hidden_from_the_hub_and_redirect_when_disabled_for_an_edition(): void
    {
        $activeEdition = Edition::create([
            'name' => 'Edition 2025/26',
            'code' => 'edition-2025-26',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'published',
            'is_active' => true,
        ]);

        $document = Document::create([
            'edition_id' => $activeEdition->id,
            'slug' => 'var-protocol',
            'title' => 'VAR Protocol',
            'type' => 'single',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        DocumentTranslation::create([
            'document_id' => $document->id,
            'language_code' => 'en',
            'title' => 'VAR Protocol',
        ]);

        $page = DocumentPage::create([
            'document_id' => $document->id,
            'slug' => 'overview',
            'title' => 'Overview',
            'body_html' => '<p>Overview</p>',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        DocumentPageTranslation::create([
            'document_page_id' => $page->id,
            'language_code' => 'en',
            'title' => 'Overview',
            'body_html' => '<p>Overview</p>',
        ]);

        FeatureVisibility::create([
            'feature_key' => LotgFeatureVisibility::FEATURE_DOCUMENTS,
            'scope_type' => FeatureVisibility::SCOPE_EDITION,
            'edition_id' => $activeEdition->id,
            'is_enabled' => false,
        ]);

        $controller = app(LawController::class);
        $response = $controller->hub(Request::create(route('laws.index', ['lang' => 'en']), 'GET', ['lang' => 'en']));

        $this->assertInstanceOf(View::class, $response);
        $this->assertCount(0, $response->getData()['hubDocuments']);

        $this->get(route('documents.show', ['document' => $document, 'lang' => 'en']))
            ->assertRedirect(route('laws.index', ['lang' => 'en']));
    }

    public function test_updates_page_falls_back_to_a_visible_published_edition_when_active_is_disabled(): void
    {
        $activeEdition = Edition::create([
            'name' => 'Edition 2025/26',
            'code' => 'edition-2025-26',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'published',
            'is_active' => true,
        ]);

        $archiveEdition = Edition::create([
            'name' => 'Edition 2024/25',
            'code' => 'edition-2024-25',
            'year_start' => 2024,
            'year_end' => 2025,
            'status' => 'published',
            'is_active' => false,
        ]);

        FeatureVisibility::create([
            'feature_key' => LotgFeatureVisibility::FEATURE_LEGACY_UPDATES,
            'scope_type' => FeatureVisibility::SCOPE_EDITION,
            'edition_id' => $activeEdition->id,
            'is_enabled' => false,
        ]);

        ChangelogEntry::create([
            'edition_id' => $activeEdition->id,
            'language_code' => 'en',
            'title' => 'Hidden Active Change',
            'body' => 'Should not be shown.',
            'sort_order' => 1,
            'published_at' => now(),
        ]);

        ChangelogEntry::create([
            'edition_id' => $archiveEdition->id,
            'language_code' => 'en',
            'title' => 'Archive Change',
            'body' => 'This remains visible.',
            'sort_order' => 1,
            'published_at' => now(),
        ]);

        $controller = app(ChangelogController::class);
        $response = $controller->index(Request::create(route('updates.index', ['lang' => 'en']), 'GET', ['lang' => 'en']));

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame($archiveEdition->id, $response->getData()['selectedEdition']?->id);
        $this->assertSame('Archive Change', $response->getData()['entries']->first()?->title);
        $this->assertSame([$archiveEdition->id], $response->getData()['publishedEditions']->pluck('id')->all());
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
