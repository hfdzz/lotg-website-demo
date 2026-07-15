<?php

namespace Tests\Feature;

use App\Models\Edition;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditionAdminTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_an_edition_export_from_the_ui(): void
    {
        $this->actingAsSuperAdmin();

        $edition = Edition::create([
            'name' => 'Source Edition',
            'code' => 'source-edition',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'published',
            'is_active' => true,
        ]);

        $response = $this->get(route('admin.editions.export', $edition));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/json; charset=UTF-8');

        $payload = json_decode($response->streamedContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('source-edition', $payload['edition']['code']);
        $this->assertSame('Source Edition', $payload['edition']['name']);
    }

    public function test_admin_can_import_an_edition_json_upload_from_the_ui(): void
    {
        $this->actingAsSuperAdmin();

        $edition = Edition::create([
            'name' => 'Target Edition',
            'code' => 'target-edition',
            'year_start' => 2026,
            'year_end' => 2027,
            'status' => 'draft',
            'is_active' => false,
        ]);

        $payload = [
            'schema_version' => 1,
            'edition' => [
                'name' => 'Imported Edition Name',
                'code' => 'target-edition',
                'year_start' => 2026,
                'year_end' => 2027,
                'status' => 'draft',
                'is_active' => false,
            ],
            'media_assets' => [],
            'changelog_entries' => [],
            'documents' => [],
            'laws' => [
                [
                    'law_number' => '1',
                    'slug' => 'law-1',
                    'sort_order' => 1,
                    'status' => 'published',
                    'translations' => [
                        'id' => [
                            'title' => 'Hukum 1',
                            'subtitle' => 'Subjudul',
                            'description_text' => 'Ringkasan hukum pertama.',
                        ],
                        'en' => [
                            'title' => 'Law 1',
                            'subtitle' => 'Subtitle',
                            'description_text' => 'Summary for the first law.',
                        ],
                    ],
                    'nodes' => [
                        [
                            'node_type' => 'section',
                            'sort_order' => 1,
                            'is_published' => true,
                            'translations' => [
                                'id' => [
                                    'title' => 'Judul Bagian',
                                    'body_html' => '<p>Isi bagian.</p>',
                                    'status' => 'published',
                                ],
                                'en' => [
                                    'title' => 'Section Title',
                                    'body_html' => '<p>Section body.</p>',
                                    'status' => 'published',
                                ],
                            ],
                            'media' => [],
                            'children' => [],
                        ],
                    ],
                    'qas' => [],
                ],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent(
            'edition.json',
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $this->from(route('admin.editions.index', ['edition' => $edition->id]))
            ->post(route('admin.editions.import'), [
                'import_mode' => 'upload',
                'import_file' => $file,
                'target_edition_id' => $edition->id,
            ])
            ->assertRedirect(route('admin.editions.index', ['edition' => $edition->id]))
            ->assertSessionHas('edition_transfer_report');

        $this->assertDatabaseHas('editions', [
            'id' => $edition->id,
            'name' => 'Imported Edition Name',
            'code' => 'target-edition',
        ]);

        $this->assertDatabaseHas('laws', [
            'edition_id' => $edition->id,
            'law_number' => '1',
            'slug' => 'law-1',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('law_translations', [
            'language_code' => 'id',
            'title' => 'Hukum 1',
        ]);

        $this->assertDatabaseHas('content_nodes', [
            'law_id' => $edition->laws()->firstOrFail()->id,
            'node_type' => 'section',
            'sort_order' => 1,
        ]);
    }

    public function test_admin_can_save_an_edition_export_to_a_storage_disk_from_the_ui(): void
    {
        Storage::fake('s3');

        $this->actingAsSuperAdmin();

        $edition = Edition::create([
            'name' => 'Source Edition',
            'code' => 'source-edition',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'published',
            'is_active' => true,
        ]);

        $path = 'exports/ui-source-edition.json';

        $this->from(route('admin.editions.index', ['edition' => $edition->id]))
            ->post(route('admin.editions.export.store', $edition), [
                'export_disk' => 's3',
                'export_path' => $path,
            ])
            ->assertRedirect(route('admin.editions.index', ['edition' => $edition->id]))
            ->assertSessionHas('edition_transfer_report');

        Storage::disk('s3')->assertExists($path);

        $payload = json_decode(Storage::disk('s3')->get($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('source-edition', $payload['edition']['code']);
        $this->assertSame('Source Edition', $payload['edition']['name']);
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
