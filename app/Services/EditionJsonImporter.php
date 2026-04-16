<?php

namespace App\Services;

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
use App\Support\UniqueSlugSuffixer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class EditionJsonImporter
{
    /**
     * @param array<string, mixed> $payload
     * @return array{edition: \App\Models\Edition, counts: array<string, int>, warnings: array<int, string>}
     */
    public function import(array $payload, ?Edition $targetEdition = null, bool $replace = false): array
    {
        $this->validatePayload($payload);

        return DB::transaction(function () use ($payload, $targetEdition, $replace) {
            $warnings = [];
            $edition = $this->resolveEdition($payload, $targetEdition, $warnings);

            $hasExistingContent = $edition->laws()->exists() || $edition->documents()->exists();

            if ($hasExistingContent && ! $replace) {
                throw new InvalidArgumentException('Target edition already has content. Re-run the import with --replace to overwrite it.');
            }

            if ($replace) {
                $this->clearEditionContent($edition);
            }

            $this->applyEditionMetadata($edition, Arr::get($payload, 'edition', []), $warnings);

            $mediaKeyMap = $this->importMediaAssets(Arr::get($payload, 'media_assets', []));
            $counts = [
                'media_assets' => count($mediaKeyMap),
                'laws' => 0,
                'nodes' => 0,
                'qas' => 0,
                'documents' => 0,
                'document_pages' => 0,
                'changelog_entries' => 0,
            ];

            foreach (Arr::get($payload, 'changelog_entries', []) as $changelogPayload) {
                ChangelogEntry::create([
                    'edition_id' => $edition->id,
                    'language_code' => (string) ($changelogPayload['language_code'] ?? 'id'),
                    'title' => (string) ($changelogPayload['title'] ?? 'Update entry'),
                    'body' => (string) ($changelogPayload['body'] ?? ''),
                    'sort_order' => (int) ($changelogPayload['sort_order'] ?? $counts['changelog_entries']),
                    'published_at' => filled($changelogPayload['published_at'] ?? null)
                        ? $changelogPayload['published_at']
                        : null,
                ]);

                $counts['changelog_entries']++;
            }

            foreach (Arr::get($payload, 'documents', []) as $documentPayload) {
                $this->importDocument($edition, $documentPayload, $counts);
            }

            foreach (Arr::get($payload, 'laws', []) as $lawPayload) {
                $this->importLaw($edition, $lawPayload, $mediaKeyMap, $counts);
            }

            return [
                'edition' => $edition->fresh(),
                'counts' => $counts,
                'warnings' => $warnings,
            ];
        });
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $warnings
     */
    protected function resolveEdition(array $payload, ?Edition $targetEdition, array &$warnings): Edition
    {
        $editionData = Arr::get($payload, 'edition', []);
        $payloadCode = trim((string) ($editionData['code'] ?? ''));

        if ($payloadCode === '') {
            throw new InvalidArgumentException('Edition code is required in the import payload.');
        }

        if ($targetEdition) {
            return $targetEdition;
        }

        $existingEdition = Edition::query()->where('code', $payloadCode)->first();

        if ($existingEdition) {
            return $existingEdition;
        }

        return Edition::create([
            'name' => (string) ($editionData['name'] ?? $payloadCode),
            'code' => $payloadCode,
            'year_start' => (int) ($editionData['year_start'] ?? now()->year),
            'year_end' => (int) ($editionData['year_end'] ?? now()->year + 1),
            'status' => (string) ($editionData['status'] ?? 'draft'),
            'is_active' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $editionData
     * @param array<int, string> $warnings
     */
    protected function applyEditionMetadata(Edition $edition, array $editionData, array &$warnings): void
    {
        $desiredCode = trim((string) ($editionData['code'] ?? $edition->code));
        $codeInUse = Edition::query()
            ->where('code', $desiredCode)
            ->whereKeyNot($edition->id)
            ->exists();

        $payloadStatus = (string) ($editionData['status'] ?? $edition->status ?? 'draft');
        $status = in_array($payloadStatus, ['draft', 'published'], true) ? $payloadStatus : 'draft';

        if ($edition->is_active && $status !== 'published') {
            $warnings[] = 'Imported edition status was forced to published because the target edition is currently active.';
            $status = 'published';
        }

        if (($editionData['is_active'] ?? false) && ! $edition->is_active) {
            $warnings[] = 'The export marked this edition as active, but import does not auto-activate editions.';
        }

        $edition->update([
            'name' => (string) ($editionData['name'] ?? $edition->name),
            'code' => $codeInUse ? $edition->code : ($desiredCode !== '' ? $desiredCode : $edition->code),
            'year_start' => (int) ($editionData['year_start'] ?? $edition->year_start),
            'year_end' => (int) ($editionData['year_end'] ?? $edition->year_end),
            'status' => $status,
            'is_active' => $edition->is_active,
        ]);

        if ($codeInUse && $desiredCode !== $edition->code) {
            $warnings[] = 'Edition code was kept as '.$edition->code.' because '.$desiredCode.' already exists in this database.';
        }
    }

    protected function clearEditionContent(Edition $edition): void
    {
        $mediaAssets = MediaAsset::query()
            ->whereHas('contentNodes.law', fn ($query) => $query->where('edition_id', $edition->id))
            ->get()
            ->unique('id');

        ChangelogEntry::query()->where('edition_id', $edition->id)->delete();
        Document::query()->forEdition($edition->id)->delete();
        Law::query()->forEdition($edition->id)->delete();

        foreach ($mediaAssets as $mediaAsset) {
            if (! $mediaAsset->contentNodes()->exists()) {
                $this->deleteStoredFileIfNeeded($mediaAsset->file_path);
                $this->deleteStoredFileIfNeeded($mediaAsset->thumbnail_path);
                $mediaAsset->delete();
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $mediaPayloads
     * @return array<string, \App\Models\MediaAsset>
     */
    protected function importMediaAssets(array $mediaPayloads): array
    {
        $mediaKeyMap = [];

        foreach ($mediaPayloads as $mediaPayload) {
            $key = (string) ($mediaPayload['key'] ?? '');

            if ($key === '') {
                throw new InvalidArgumentException('Each media asset in the import payload must include a key.');
            }

            if (isset($mediaKeyMap[$key])) {
                throw new InvalidArgumentException('Duplicate media key detected in import payload: '.$key);
            }

            $filePath = $this->restoreStoredFile($mediaPayload['file'] ?? null, $mediaPayload['file_path'] ?? null);
            $thumbnailPath = $this->restoreStoredFile($mediaPayload['thumbnail_file'] ?? null, $mediaPayload['thumbnail_path'] ?? null);

            $mediaKeyMap[$key] = MediaAsset::create([
                'asset_type' => (string) ($mediaPayload['asset_type'] ?? 'file'),
                'storage_type' => (string) ($mediaPayload['storage_type'] ?? 'external'),
                'is_library_item' => (bool) ($mediaPayload['is_library_item'] ?? false),
                'file_path' => $filePath,
                'external_url' => Arr::get($mediaPayload, 'external_url'),
                'thumbnail_path' => $thumbnailPath,
                'caption' => Arr::get($mediaPayload, 'caption'),
                'credit' => Arr::get($mediaPayload, 'credit'),
            ]);
        }

        return $mediaKeyMap;
    }

    /**
     * @param array<string, mixed> $documentPayload
     * @param array<string, int> $counts
     */
    protected function importDocument(Edition $edition, array $documentPayload, array &$counts): void
    {
        $translations = Arr::get($documentPayload, 'translations', []);
        $baseTitle = (string) (
            Arr::get($translations, 'id.title')
            ?? Arr::get($translations, 'en.title')
            ?? Arr::get($documentPayload, 'base_title')
            ?? 'Document'
        );

        $slug = UniqueSlugSuffixer::ensureUnique(
            (string) ($documentPayload['slug'] ?? 'document'),
            fn (string $candidate) => Document::query()
                ->where('edition_id', $edition->id)
                ->where('slug', $candidate)
                ->exists()
        );

        $document = Document::create([
            'edition_id' => $edition->id,
            'slug' => $slug,
            'title' => $baseTitle,
            'type' => (string) ($documentPayload['type'] ?? 'single'),
            'sort_order' => (int) ($documentPayload['sort_order'] ?? 1),
            'status' => (string) ($documentPayload['status'] ?? 'draft'),
        ]);

        $counts['documents']++;

        foreach ($translations as $languageCode => $translationPayload) {
            DocumentTranslation::create([
                'document_id' => $document->id,
                'language_code' => (string) $languageCode,
                'title' => (string) ($translationPayload['title'] ?? $baseTitle),
            ]);
        }

        foreach (Arr::get($documentPayload, 'pages', []) as $pagePayload) {
            $pageTranslations = Arr::get($pagePayload, 'translations', []);
            $pageBaseTitle = (string) (
                Arr::get($pageTranslations, 'id.title')
                ?? Arr::get($pageTranslations, 'en.title')
                ?? Arr::get($pagePayload, 'base_title')
                ?? $baseTitle
            );
            $pageBaseBody = Arr::get($pageTranslations, 'id.body_html')
                ?? Arr::get($pageTranslations, 'en.body_html')
                ?? Arr::get($pagePayload, 'base_body_html');

            $page = DocumentPage::create([
                'document_id' => $document->id,
                'slug' => (string) ($pagePayload['slug'] ?? 'page-'.($counts['document_pages'] + 1)),
                'title' => $pageBaseTitle,
                'body_html' => $pageBaseBody,
                'sort_order' => (int) ($pagePayload['sort_order'] ?? ($counts['document_pages'] + 1)),
                'status' => (string) ($pagePayload['status'] ?? 'draft'),
            ]);

            $counts['document_pages']++;

            foreach ($pageTranslations as $languageCode => $translationPayload) {
                DocumentPageTranslation::create([
                    'document_page_id' => $page->id,
                    'language_code' => (string) $languageCode,
                    'title' => (string) ($translationPayload['title'] ?? $pageBaseTitle),
                    'body_html' => Arr::get($translationPayload, 'body_html'),
                ]);
            }
        }
    }

    /**
     * @param array<string, mixed> $lawPayload
     * @param array<string, \App\Models\MediaAsset> $mediaKeyMap
     * @param array<string, int> $counts
     */
    protected function importLaw(Edition $edition, array $lawPayload, array $mediaKeyMap, array &$counts): void
    {
        $translations = Arr::get($lawPayload, 'translations', []);
        $baseTitle = (string) (
            Arr::get($translations, 'id.title')
            ?? Arr::get($translations, 'en.title')
            ?? 'Law '.(string) ($lawPayload['law_number'] ?? ($counts['laws'] + 1))
        );

        $slug = UniqueSlugSuffixer::ensureUnique(
            (string) ($lawPayload['slug'] ?? $baseTitle),
            fn (string $candidate) => Law::query()
                ->where('edition_id', $edition->id)
                ->where('slug', $candidate)
                ->exists()
        );

        $law = Law::create([
            'edition_id' => $edition->id,
            'law_number' => (string) ($lawPayload['law_number'] ?? ($counts['laws'] + 1)),
            'slug' => $slug,
            'sort_order' => (int) ($lawPayload['sort_order'] ?? ($counts['laws'] + 1)),
            'status' => (string) ($lawPayload['status'] ?? 'draft'),
        ]);

        $counts['laws']++;

        foreach ($translations as $languageCode => $translationPayload) {
            LawTranslation::create([
                'law_id' => $law->id,
                'language_code' => (string) $languageCode,
                'title' => (string) ($translationPayload['title'] ?? $baseTitle),
                'subtitle' => Arr::get($translationPayload, 'subtitle'),
                'description_text' => Arr::get($translationPayload, 'description_text'),
            ]);
        }

        foreach (Arr::get($lawPayload, 'nodes', []) as $nodePayload) {
            $this->importNode($law, null, $nodePayload, $mediaKeyMap, $counts);
        }

        foreach (Arr::get($lawPayload, 'qas', []) as $qaPayload) {
            $qa = LawQa::create([
                'law_id' => $law->id,
                'sort_order' => (int) ($qaPayload['sort_order'] ?? ($counts['qas'] + 1)),
                'is_published' => (bool) ($qaPayload['is_published'] ?? false),
            ]);

            $counts['qas']++;

            foreach (Arr::get($qaPayload, 'translations', []) as $languageCode => $translationPayload) {
                LawQaTranslation::create([
                    'law_qa_id' => $qa->id,
                    'language_code' => (string) $languageCode,
                    'question' => (string) ($translationPayload['question'] ?? 'Question'),
                    'answer_html' => Arr::get($translationPayload, 'answer_html'),
                    'status' => (string) ($translationPayload['status'] ?? 'draft'),
                ]);
            }
        }
    }

    /**
     * @param array<string, mixed> $nodePayload
     * @param array<string, \App\Models\MediaAsset> $mediaKeyMap
     * @param array<string, int> $counts
     */
    protected function importNode(Law $law, ?ContentNode $parentNode, array $nodePayload, array $mediaKeyMap, array &$counts): ContentNode
    {
        $nodeType = (string) ($nodePayload['node_type'] ?? '');

        if (! in_array($nodeType, ['section', 'rich_text', 'image', 'video_group', 'resource_list'], true)) {
            throw new InvalidArgumentException('Unsupported node type in import payload: '.$nodeType);
        }

        $node = ContentNode::create([
            'law_id' => $law->id,
            'parent_id' => $parentNode?->id,
            'node_type' => $nodeType,
            'sort_order' => (int) ($nodePayload['sort_order'] ?? ($counts['nodes'] + 1)),
            'is_published' => (bool) ($nodePayload['is_published'] ?? false),
            'settings_json' => Arr::get($nodePayload, 'settings_json'),
        ]);

        $counts['nodes']++;

        foreach (Arr::get($nodePayload, 'translations', []) as $languageCode => $translationPayload) {
            ContentNodeTranslation::create([
                'content_node_id' => $node->id,
                'language_code' => (string) $languageCode,
                'title' => Arr::get($translationPayload, 'title'),
                'body_html' => Arr::get($translationPayload, 'body_html'),
                'status' => (string) ($translationPayload['status'] ?? 'draft'),
            ]);
        }

        $mediaSyncPayload = [];

        foreach (Arr::get($nodePayload, 'media', []) as $mediaReference) {
            $mediaKey = (string) ($mediaReference['media_key'] ?? '');

            if ($mediaKey === '' || ! isset($mediaKeyMap[$mediaKey])) {
                throw new InvalidArgumentException('Missing media asset for key: '.$mediaKey);
            }

            $mediaSyncPayload[$mediaKeyMap[$mediaKey]->id] = [
                'sort_order' => (int) ($mediaReference['sort_order'] ?? 1),
            ];
        }

        if ($mediaSyncPayload !== []) {
            $node->mediaAssets()->sync($mediaSyncPayload);
        }

        foreach (Arr::get($nodePayload, 'children', []) as $childPayload) {
            $this->importNode($law, $node, $childPayload, $mediaKeyMap, $counts);
        }

        return $node;
    }

    /**
     * @param array<string, mixed>|null $filePayload
     */
    protected function restoreStoredFile(?array $filePayload, ?string $fallbackPath): ?string
    {
        $path = (string) ($filePayload['path'] ?? $fallbackPath ?? '');

        if ($path === '') {
            return null;
        }

        $contents = $filePayload['contents_base64'] ?? null;

        if ($contents && ! str_starts_with($path, 'http://') && ! str_starts_with($path, 'https://') && ! str_starts_with($path, 'demo/')) {
            Storage::disk('public')->put($path, base64_decode((string) $contents));
        }

        return $path;
    }

    protected function deleteStoredFileIfNeeded(?string $path): void
    {
        if (! $path || str_starts_with($path, 'demo/')) {
            return;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function validatePayload(array $payload): void
    {
        if (($payload['schema_version'] ?? null) !== EditionJsonExporter::SCHEMA_VERSION) {
            throw new InvalidArgumentException('Unsupported edition JSON schema version.');
        }

        if (! is_array($payload['edition'] ?? null)) {
            throw new InvalidArgumentException('Edition import payload must include an edition object.');
        }

        if (! is_array($payload['laws'] ?? null)) {
            throw new InvalidArgumentException('Edition import payload must include a laws array.');
        }

        if (! is_array($payload['documents'] ?? null)) {
            throw new InvalidArgumentException('Edition import payload must include a documents array.');
        }

        if (! is_array($payload['media_assets'] ?? null)) {
            throw new InvalidArgumentException('Edition import payload must include a media_assets array.');
        }

        if (array_key_exists('changelog_entries', $payload) && ! is_array($payload['changelog_entries'])) {
            throw new InvalidArgumentException('Edition import payload changelog_entries must be an array when present.');
        }
    }
}
