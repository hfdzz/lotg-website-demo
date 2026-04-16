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
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        config(['lotg.export_default_dir' => 'storage/app/testing-default-exports']);

        $edition = Edition::create([
            'name' => 'Config Export Edition',
            'code' => 'config-export-edition',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'draft',
            'is_active' => false,
        ]);

        $targetDirectory = storage_path('app/testing-default-exports');
        File::ensureDirectoryExists($targetDirectory);

        foreach (File::glob($targetDirectory.DIRECTORY_SEPARATOR.'lotg-edition-'.$edition->code.'-*.json') as $existingFile) {
            File::delete($existingFile);
        }

        $this->artisan('lotg:edition-export', [
            'edition' => (string) $edition->id,
        ])->assertExitCode(0);

        $matches = File::glob($targetDirectory.DIRECTORY_SEPARATOR.'lotg-edition-'.$edition->code.'-*.json');

        $this->assertCount(1, $matches);
    }
}
