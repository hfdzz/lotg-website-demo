<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\Edition;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentAdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_slugs_are_generated_and_unique_per_edition(): void
    {
        $this->actingAsSuperAdmin();

        $edition = Edition::create([
            'name' => 'Edition A',
            'code' => 'edition-a',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'published',
            'is_active' => true,
        ]);

        $otherEdition = Edition::create([
            'name' => 'Edition B',
            'code' => 'edition-b',
            'year_start' => 2026,
            'year_end' => 2027,
            'status' => 'published',
            'is_active' => false,
        ]);

        $this->post(route('admin.documents.store', ['edition' => $edition]), $this->documentPayload([
            'title_id' => 'Protokol VAR',
            'slug' => '',
        ]))->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'edition_id' => $edition->id,
            'slug' => 'protokol-var',
        ]);

        $this->post(route('admin.documents.store', ['edition' => $otherEdition]), $this->documentPayload([
            'title_id' => 'Protokol VAR',
            'slug' => '',
        ]))->assertRedirect();

        $this->assertSame(2, Document::query()->where('slug', 'protokol-var')->count());

        $this->from(route('admin.documents.index', ['edition' => $edition]))
            ->post(route('admin.documents.store', ['edition' => $edition]), $this->documentPayload([
                'title_id' => 'Protokol VAR Duplikat',
                'slug' => 'protokol-var',
            ]))
            ->assertRedirect(route('admin.documents.index', ['edition' => $edition]))
            ->assertSessionHasErrors('slug');
    }

    public function test_document_page_reordering_shifts_siblings(): void
    {
        $this->actingAsSuperAdmin();

        $edition = Edition::create([
            'name' => 'Edition A',
            'code' => 'edition-a',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'published',
            'is_active' => true,
        ]);

        $document = Document::create([
            'edition_id' => $edition->id,
            'slug' => 'guidelines',
            'title' => 'Guidelines',
            'type' => 'collection',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        $pages = collect(['one', 'two', 'three', 'four'])->map(function (string $slug, int $index) use ($document) {
            return DocumentPage::create([
                'document_id' => $document->id,
                'slug' => $slug,
                'title' => ucfirst($slug),
                'body_html' => '<p>'.$slug.'</p>',
                'sort_order' => $index + 1,
                'status' => 'published',
            ]);
        });

        $this->patch(route('admin.documents.update', ['edition' => $edition, 'document' => $document]), [
            'title_id' => 'Guidelines',
            'title_en' => 'Guidelines',
            'slug' => 'guidelines',
            'type' => 'collection',
            'sort_order' => 1,
            'status' => 'published',
            'pages' => $pages->map(fn (DocumentPage $page) => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title_id' => $page->title,
                'title_en' => $page->title,
                'body_html_id' => $page->body_html,
                'body_html_en' => $page->body_html,
                'sort_order' => $page->slug === 'four' ? 2 : $page->sort_order,
                'status' => 'published',
                'media' => [],
            ])->all(),
        ])->assertRedirect(route('admin.documents.edit', ['edition' => $edition, 'document' => $document]));

        $this->assertSame(
            ['one', 'four', 'two', 'three'],
            $document->pages()->orderBy('sort_order')->pluck('slug')->all(),
        );

        $this->assertSame([1, 2, 3, 4], $document->pages()->orderBy('sort_order')->pluck('sort_order')->all());
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

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function documentPayload(array $overrides = []): array
    {
        return [
            ...[
                'title_id' => 'Dokumen',
                'title_en' => 'Document',
                'slug' => '',
                'type' => 'single',
                'sort_order' => 1,
                'status' => 'published',
            ],
            ...$overrides,
        ];
    }
}
