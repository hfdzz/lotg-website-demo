<?php

namespace Tests\Feature;

use App\Models\ChangelogEntry;
use App\Models\ContentNode;
use App\Models\ContentNodeTranslation;
use App\Models\Document;
use App\Models\DocumentPage;
use App\Models\DocumentPageTranslation;
use App\Models\DocumentTranslation;
use App\Models\Edition;
use App\Models\Law;
use App\Models\LawQa;
use App\Models\LawQaTranslation;
use App\Models\LawTranslation;
use App\Models\MediaAsset;
use App\Services\EditionJsonExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditionJsonImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_and_imports_an_edition_as_json(): void
    {
        Storage::fake('public');

        $sourceEdition = Edition::create([
            'name' => 'Source Edition',
            'code' => 'source-edition',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'published',
            'is_active' => true,
        ]);

        $law = Law::create([
            'edition_id' => $sourceEdition->id,
            'law_number' => '1',
            'slug' => 'the-field-of-play',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        LawTranslation::create([
            'law_id' => $law->id,
            'language_code' => 'id',
            'title' => 'Lapangan Permainan',
            'subtitle' => 'Versi Uji',
            'description_text' => 'Deskripsi hukum untuk ekspor.',
        ]);

        LawTranslation::create([
            'law_id' => $law->id,
            'language_code' => 'en',
            'title' => 'The Field of Play',
            'subtitle' => 'Test Version',
            'description_text' => 'Law description for export.',
        ]);

        $sectionNode = ContentNode::create([
            'law_id' => $law->id,
            'parent_id' => null,
            'node_type' => 'section',
            'sort_order' => 1,
            'is_published' => true,
            'settings_json' => ['layout' => 'article'],
        ]);

        ContentNodeTranslation::create([
            'content_node_id' => $sectionNode->id,
            'language_code' => 'id',
            'title' => 'Permukaan Lapangan',
            'body_html' => '<p>Rumput alami atau buatan.</p>',
            'status' => 'published',
        ]);

        ContentNodeTranslation::create([
            'content_node_id' => $sectionNode->id,
            'language_code' => 'en',
            'title' => 'Playing Surface',
            'body_html' => '<p>Natural or artificial turf.</p>',
            'status' => 'published',
        ]);

        $imageNode = ContentNode::create([
            'law_id' => $law->id,
            'parent_id' => $sectionNode->id,
            'node_type' => 'image',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        ContentNodeTranslation::create([
            'content_node_id' => $imageNode->id,
            'language_code' => 'id',
            'title' => 'Contoh Gambar',
            'body_html' => null,
            'status' => 'published',
        ]);

        ContentNodeTranslation::create([
            'content_node_id' => $imageNode->id,
            'language_code' => 'en',
            'title' => 'Image Example',
            'body_html' => null,
            'status' => 'published',
        ]);

        Storage::disk('public')->put('lotg-images/source-field.png', 'fake-image-content');

        $imageAsset = MediaAsset::create([
            'asset_type' => 'image',
            'storage_type' => 'upload',
            'is_library_item' => true,
            'file_path' => 'lotg-images/source-field.png',
            'caption' => 'Field diagram',
            'credit' => 'IFAB',
        ]);

        $imageNode->mediaAssets()->sync([
            $imageAsset->id => ['sort_order' => 1],
        ]);

        $videoNode = ContentNode::create([
            'law_id' => $law->id,
            'parent_id' => null,
            'node_type' => 'video_group',
            'sort_order' => 2,
            'is_published' => true,
            'settings_json' => ['layout' => 'stacked'],
        ]);

        ContentNodeTranslation::create([
            'content_node_id' => $videoNode->id,
            'language_code' => 'id',
            'title' => 'Video Penjelasan',
            'body_html' => null,
            'status' => 'published',
        ]);

        ContentNodeTranslation::create([
            'content_node_id' => $videoNode->id,
            'language_code' => 'en',
            'title' => 'Video Explainer',
            'body_html' => null,
            'status' => 'published',
        ]);

        $videoAsset = MediaAsset::create([
            'asset_type' => 'video',
            'storage_type' => 'youtube',
            'is_library_item' => true,
            'external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'caption' => 'Explainer video',
            'credit' => 'YouTube',
        ]);

        $videoNode->mediaAssets()->sync([
            $videoAsset->id => ['sort_order' => 1],
        ]);

        $qa = LawQa::create([
            'law_id' => $law->id,
            'sort_order' => 1,
            'is_published' => true,
        ]);

        LawQaTranslation::create([
            'law_qa_id' => $qa->id,
            'language_code' => 'id',
            'question' => 'Apakah garis lapangan wajib terlihat jelas?',
            'answer_html' => '<p>Ya, garis harus terlihat jelas.</p>',
            'status' => 'published',
        ]);

        LawQaTranslation::create([
            'law_qa_id' => $qa->id,
            'language_code' => 'en',
            'question' => 'Must the field markings be clearly visible?',
            'answer_html' => '<p>Yes, the markings must be clearly visible.</p>',
            'status' => 'published',
        ]);

        $document = Document::create([
            'edition_id' => $sourceEdition->id,
            'slug' => 'var-protocol',
            'title' => 'VAR Protocol',
            'type' => 'single',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        DocumentTranslation::create([
            'document_id' => $document->id,
            'language_code' => 'id',
            'title' => 'Protokol VAR',
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
            'body_html' => '<p>Initial page body.</p>',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        DocumentPageTranslation::create([
            'document_page_id' => $page->id,
            'language_code' => 'id',
            'title' => 'Ikhtisar',
            'body_html' => '<p>Isi halaman awal.</p>',
        ]);

        DocumentPageTranslation::create([
            'document_page_id' => $page->id,
            'language_code' => 'en',
            'title' => 'Overview',
            'body_html' => '<p>Initial page body.</p>',
        ]);

        ChangelogEntry::create([
            'edition_id' => $sourceEdition->id,
            'language_code' => 'id',
            'title' => 'Perubahan Penting',
            'body' => 'Ringkasan perubahan utama.',
            'sort_order' => 1,
            'published_at' => now(),
        ]);

        $exportPath = storage_path('app/testing/edition-export.json');
        File::ensureDirectoryExists(dirname($exportPath));

        $this->artisan('lotg:edition-export', [
            'edition' => (string) $sourceEdition->id,
            'path' => $exportPath,
        ])->assertExitCode(0);

        $payload = json_decode(File::get($exportPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $payload['laws']);
        $this->assertCount(1, $payload['documents']);
        $this->assertCount(2, $payload['media_assets']);
        $this->assertCount(1, $payload['changelog_entries']);
        $this->assertNotEmpty($payload['media_assets'][0]['key']);

        $payload['edition']['code'] = 'imported-edition';
        $payload['edition']['name'] = 'Imported Edition';
        $payload['edition']['is_active'] = false;

        File::put(
            $exportPath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        $this->artisan('lotg:edition-import', [
            'path' => $exportPath,
        ])->assertExitCode(0);

        $importedEdition = Edition::query()->where('code', 'imported-edition')->firstOrFail();
        $importedLaw = Law::query()
            ->where('edition_id', $importedEdition->id)
            ->with(['translations', 'contentNodes.translations', 'contentNodes.mediaAssets', 'qas.translations'])
            ->firstOrFail();
        $importedDocument = Document::query()
            ->where('edition_id', $importedEdition->id)
            ->with(['translations', 'pages.translations'])
            ->firstOrFail();

        $this->assertSame('Imported Edition', $importedEdition->name);
        $this->assertSame('published', $importedEdition->status);
        $this->assertFalse($importedEdition->is_active);

        $this->assertSame('1', $importedLaw->law_number);
        $this->assertCount(2, $importedLaw->translations);
        $this->assertCount(3, $importedLaw->contentNodes);
        $this->assertCount(1, $importedLaw->qas);
        $this->assertSame('Lapangan Permainan', $importedLaw->translations->firstWhere('language_code', 'id')?->title);

        $importedImageNode = $importedLaw->contentNodes->firstWhere('node_type', 'image');
        $this->assertNotNull($importedImageNode);
        $this->assertCount(1, $importedImageNode->mediaAssets);
        $this->assertSame('Field diagram', $importedImageNode->mediaAssets->first()?->caption);
        $this->assertTrue(Storage::disk('public')->exists($importedImageNode->mediaAssets->first()?->file_path));

        $importedVideoNode = $importedLaw->contentNodes->firstWhere('node_type', 'video_group');
        $this->assertNotNull($importedVideoNode);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $importedVideoNode->mediaAssets->first()?->external_url);

        $this->assertSame('var-protocol', $importedDocument->slug);
        $this->assertCount(2, $importedDocument->translations);
        $this->assertCount(1, $importedDocument->pages);
        $this->assertSame('Protokol VAR', $importedDocument->translations->firstWhere('language_code', 'id')?->title);
        $this->assertSame(1, ChangelogEntry::query()->where('edition_id', $importedEdition->id)->count());
        $this->assertSame('Perubahan Penting', ChangelogEntry::query()->where('edition_id', $importedEdition->id)->first()?->title);

        $payload['laws'][0]['translations']['id']['title'] = 'Lapangan Versi Impor Ulang';
        $payload['documents'][0]['translations']['id']['title'] = 'Protokol VAR Ulang';
        $payload['changelog_entries'][0]['title'] = 'Perubahan Penting Ulang';

        File::put(
            $exportPath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        $this->artisan('lotg:edition-import', [
            'path' => $exportPath,
            '--edition' => 'imported-edition',
            '--replace' => true,
        ])->assertExitCode(0);

        $this->assertSame(1, Law::query()->where('edition_id', $importedEdition->id)->count());
        $this->assertSame(1, Document::query()->where('edition_id', $importedEdition->id)->count());
        $this->assertSame(1, ChangelogEntry::query()->where('edition_id', $importedEdition->id)->count());

        $reimportedLaw = Law::query()
            ->where('edition_id', $importedEdition->id)
            ->with('translations')
            ->firstOrFail();
        $reimportedDocument = Document::query()
            ->where('edition_id', $importedEdition->id)
            ->with('translations')
            ->firstOrFail();

        $this->assertSame('Lapangan Versi Impor Ulang', $reimportedLaw->translations->firstWhere('language_code', 'id')?->title);
        $this->assertSame('Protokol VAR Ulang', $reimportedDocument->translations->firstWhere('language_code', 'id')?->title);
        $this->assertSame('Perubahan Penting Ulang', ChangelogEntry::query()->where('edition_id', $importedEdition->id)->first()?->title);
    }

    public function test_it_uses_the_configured_default_export_directory_when_path_is_omitted(): void
    {
        $exportDirectory = 'storage/app/testing-default-exports/'.Str::random(8);
        config(['lotg.export_default_dir' => $exportDirectory]);

        $edition = Edition::create([
            'name' => 'Config Export Edition',
            'code' => 'config-export-edition',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'draft',
            'is_active' => false,
        ]);

        $targetDirectory = base_path($exportDirectory);
        File::ensureDirectoryExists($targetDirectory);

        $this->artisan('lotg:edition-export', [
            'edition' => (string) $edition->id,
        ])->assertExitCode(0);

        $matches = File::glob($targetDirectory.DIRECTORY_SEPARATOR.'lotg-edition-'.$edition->code.'-*.json');

        $this->assertCount(1, $matches);
    }

    public function test_it_can_dry_run_an_import_without_saving_changes(): void
    {
        $path = $this->writeEditionJson('edition-dry-run-pass.json', $this->makeDryRunPayload());

        $this->artisan('lotg:edition-import', [
            'path' => $path,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Edition dry run summary for Dry Run Edition (dry-run-edition).')
            ->expectsOutputToContain('Laws: 1')
            ->expectsOutputToContain('Nodes: 2')
            ->expectsOutputToContain('Q&A: 1')
            ->expectsOutputToContain('Documents: 1')
            ->expectsOutputToContain('Document pages: 1')
            ->expectsOutputToContain('Changelog entries: 1')
            ->expectsOutputToContain('Media assets: 1')
            ->expectsOutputToContain('Dry run passed. No changes were saved.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('editions', ['code' => 'dry-run-edition']);
        $this->assertSame(0, Law::query()->count());
        $this->assertSame(0, Document::query()->count());
        $this->assertSame(0, ChangelogEntry::query()->count());
    }

    public function test_it_can_import_json_from_a_storage_disk(): void
    {
        Storage::fake('s3');

        $payload = $this->makeDryRunPayload();
        Storage::disk('s3')->put(
            'lotg-exports/edition-from-s3.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        $this->artisan('lotg:edition-import', [
            'path' => 'lotg-exports/edition-from-s3.json',
            '--disk' => 's3',
        ])
            ->expectsOutputToContain('Edition import completed for Dry Run Edition (dry-run-edition).')
            ->assertExitCode(0);

        $edition = Edition::query()->where('code', 'dry-run-edition')->firstOrFail();

        $this->assertSame(1, Law::query()->where('edition_id', $edition->id)->count());
        $this->assertSame(1, Document::query()->where('edition_id', $edition->id)->count());
        $this->assertSame(1, ChangelogEntry::query()->where('edition_id', $edition->id)->count());
    }

    public function test_dry_run_reports_blocking_errors_without_saving_changes(): void
    {
        $payload = $this->makeDryRunPayload();
        $payload['laws'][] = [
            'law_number' => '2',
            'slug' => 'law-one',
            'sort_order' => 2,
            'status' => 'published',
            'translations' => [
                'id' => [
                    'title' => 'Hukum Duplikat',
                ],
            ],
            'nodes' => [
                [
                    'node_type' => 'video_group',
                    'sort_order' => 1,
                    'is_published' => true,
                    'translations' => [
                        'id' => [
                            'title' => 'Video Rusak',
                        ],
                    ],
                    'media' => [
                        ['media_key' => 'missing-media', 'sort_order' => 1],
                    ],
                    'children' => [],
                ],
            ],
            'qas' => [],
        ];

        $path = $this->writeEditionJson('edition-dry-run-fail.json', $payload);

        $this->artisan('lotg:edition-import', [
            'path' => $path,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Laws: 2')
            ->expectsOutputToContain('Dry run found blocking issues. No changes were saved.')
            ->expectsOutputToContain('duplicates law slug law-one in this payload')
            ->expectsOutputToContain('Missing media asset for key: missing-media')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('editions', ['code' => 'dry-run-edition']);
        $this->assertSame(0, Law::query()->count());
        $this->assertSame(0, Document::query()->count());
        $this->assertSame(0, MediaAsset::query()->count());
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeDryRunPayload(): array
    {
        return [
            'schema_version' => EditionJsonExporter::SCHEMA_VERSION,
            'edition' => [
                'name' => 'Dry Run Edition',
                'code' => 'dry-run-edition',
                'year_start' => 2026,
                'year_end' => 2027,
                'status' => 'published',
                'is_active' => false,
            ],
            'media_assets' => [
                [
                    'key' => 'media-intro-video',
                    'asset_type' => 'video',
                    'storage_type' => 'youtube',
                    'is_library_item' => true,
                    'file_path' => null,
                    'external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'thumbnail_path' => null,
                    'caption' => 'Intro video',
                    'credit' => 'YouTube',
                    'file' => null,
                    'thumbnail_file' => null,
                ],
            ],
            'changelog_entries' => [
                [
                    'language_code' => 'id',
                    'title' => 'Perubahan Edisi',
                    'body' => 'Ringkasan perubahan.',
                    'sort_order' => 1,
                    'published_at' => now()->toIso8601String(),
                ],
            ],
            'documents' => [
                [
                    'slug' => 'var-protocol',
                    'type' => 'single',
                    'sort_order' => 1,
                    'status' => 'published',
                    'base_title' => 'VAR Protocol',
                    'translations' => [
                        'id' => [
                            'title' => 'Protokol VAR',
                        ],
                        'en' => [
                            'title' => 'VAR Protocol',
                        ],
                    ],
                    'pages' => [
                        [
                            'slug' => 'overview',
                            'sort_order' => 1,
                            'status' => 'published',
                            'base_title' => 'Overview',
                            'base_body_html' => '<p>Overview body.</p>',
                            'translations' => [
                                'id' => [
                                    'title' => 'Ikhtisar',
                                    'body_html' => '<p>Isi ikhtisar.</p>',
                                ],
                                'en' => [
                                    'title' => 'Overview',
                                    'body_html' => '<p>Overview body.</p>',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'laws' => [
                [
                    'law_number' => '1',
                    'slug' => 'law-one',
                    'sort_order' => 1,
                    'status' => 'published',
                    'translations' => [
                        'id' => [
                            'title' => 'Hukum Satu',
                            'subtitle' => null,
                            'description_text' => 'Deskripsi hukum satu.',
                        ],
                        'en' => [
                            'title' => 'Law One',
                            'subtitle' => null,
                            'description_text' => 'Law one description.',
                        ],
                    ],
                    'nodes' => [
                        [
                            'node_type' => 'section',
                            'sort_order' => 1,
                            'is_published' => true,
                            'settings_json' => null,
                            'translations' => [
                                'id' => [
                                    'title' => 'Pendahuluan',
                                    'body_html' => '<p>Isi pendahuluan.</p>',
                                    'status' => 'published',
                                ],
                                'en' => [
                                    'title' => 'Introduction',
                                    'body_html' => '<p>Intro body.</p>',
                                    'status' => 'published',
                                ],
                            ],
                            'media' => [],
                            'children' => [
                                [
                                    'node_type' => 'video_group',
                                    'sort_order' => 1,
                                    'is_published' => true,
                                    'settings_json' => null,
                                    'translations' => [
                                        'id' => [
                                            'title' => 'Video Penjelasan',
                                            'body_html' => null,
                                            'status' => 'published',
                                        ],
                                        'en' => [
                                            'title' => 'Video Explainer',
                                            'body_html' => null,
                                            'status' => 'published',
                                        ],
                                    ],
                                    'media' => [
                                        ['media_key' => 'media-intro-video', 'sort_order' => 1],
                                    ],
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                    'qas' => [
                        [
                            'sort_order' => 1,
                            'is_published' => true,
                            'translations' => [
                                'id' => [
                                    'question' => 'Apa inti hukum ini?',
                                    'answer_html' => '<p>Ini jawaban singkat.</p>',
                                    'status' => 'published',
                                ],
                                'en' => [
                                    'question' => 'What is the core of this law?',
                                    'answer_html' => '<p>This is the short answer.</p>',
                                    'status' => 'published',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function writeEditionJson(string $filename, array $payload): string
    {
        $path = storage_path('app/testing/'.$filename);
        File::ensureDirectoryExists(dirname($path));
        File::put(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        return $path;
    }
}
