<?php

namespace Tests\Feature;

use App\Models\ContentNode;
use App\Models\ContentNodeTranslation;
use App\Models\Edition;
use App\Models\Law;
use App\Models\LawTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_law_page_renders_core_seo_metadata(): void
    {
        $edition = Edition::create([
            'name' => '2026/27',
            'code' => '2026-27',
            'year_start' => 2026,
            'year_end' => 2027,
            'status' => 'published',
            'is_active' => true,
        ]);

        $law = Law::create([
            'edition_id' => $edition->id,
            'law_number' => '1',
            'slug' => 'law-1-the-field-of-play',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        LawTranslation::create([
            'law_id' => $law->id,
            'language_code' => 'en',
            'title' => 'The Field of Play',
            'subtitle' => null,
            'description_text' => null,
        ]);

        $node = ContentNode::create([
            'law_id' => $law->id,
            'node_type' => 'section',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        ContentNodeTranslation::create([
            'content_node_id' => $node->id,
            'language_code' => 'en',
            'title' => 'Field surface',
            'body_html' => null,
            'status' => 'published',
        ]);

        $this->get('/laws/law-1-the-field-of-play?lang=en')
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical" href="'.url('/laws/law-1-the-field-of-play').'?lang=en">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('<script type="application/ld+json">', false);
    }

    public function test_sitemap_lists_published_public_laws_and_excludes_drafts(): void
    {
        $edition = Edition::create([
            'name' => '2026/27',
            'code' => '2026-27',
            'year_start' => 2026,
            'year_end' => 2027,
            'status' => 'published',
            'is_active' => true,
        ]);

        Law::create([
            'edition_id' => $edition->id,
            'law_number' => '1',
            'slug' => 'law-1-the-field-of-play',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        Law::create([
            'edition_id' => $edition->id,
            'law_number' => '2',
            'slug' => 'law-2-the-ball',
            'sort_order' => 2,
            'status' => 'draft',
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee(url('/laws/law-1-the-field-of-play').'?lang=id', false)
            ->assertSee(url('/laws/law-1-the-field-of-play').'?lang=en', false)
            ->assertDontSee('law-2-the-ball');
    }

    public function test_login_page_is_not_indexable(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
