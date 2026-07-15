<?php

namespace Tests\Feature;

use App\Models\Edition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEditionNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_editions_page_links_archive_editions_to_law_listing_route(): void
    {
        Edition::create([
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

        $response = $this->get(route('editions.index', ['lang' => 'id']));

        $response->assertOk();
        $response->assertSee('/laws?edition='.$archiveEdition->id.'&amp;lang=id', false);
        $response->assertDontSee('/?edition='.$archiveEdition->id.'&amp;lang=id', false);
    }
}
