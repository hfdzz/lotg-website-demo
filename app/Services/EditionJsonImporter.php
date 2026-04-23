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
use App\Models\LawQaOption;
use App\Models\LawQaOptionTranslation;
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
    protected const SUPPORTED_NODE_TYPES = ['section', 'rich_text', 'image', 'video_group', 'resource_list'];

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
                'qa_options' => 0,
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
                $this->importDocument($edition, $documentPayload, $mediaKeyMap, $counts);
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
     * @return array{edition: \App\Models\Edition, counts: array<string, int>, warnings: array<int, string>, errors: array<int, string>, can_import: bool}
     */
    public function dryRun(array $payload, ?Edition $targetEdition = null, bool $replace = false): array
    {
        $warnings = [];
        $errors = [];
        $counts = $this->summarizePayload($payload);

        try {
            $this->validatePayload($payload);
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();

            return [
                'edition' => $this->previewEdition($payload, $targetEdition),
                'counts' => $counts,
                'warnings' => $warnings,
                'errors' => $errors,
                'can_import' => false,
            ];
        }

        $edition = $this->previewEdition($payload, $targetEdition);

        $this->inspectImportTarget($edition, Arr::get($payload, 'edition', []), $replace, $warnings, $errors);

        $knownMediaKeys = $this->analyzeMediaAssets(Arr::get($payload, 'media_assets', []), $warnings, $errors);
        $this->analyzeDocuments(Arr::get($payload, 'documents', []), $knownMediaKeys, $warnings, $errors);
        $this->analyzeLaws(Arr::get($payload, 'laws', []), $knownMediaKeys, $warnings, $errors);
        $this->analyzeChangelogEntries(Arr::get($payload, 'changelog_entries', []), $warnings, $errors);

        return [
            'edition' => $edition,
            'counts' => $counts,
            'warnings' => $warnings,
            'errors' => $errors,
            'can_import' => $errors === [],
        ];
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
            ->orWhereHas('documentPages.document', fn ($query) => $query->where('edition_id', $edition->id))
            ->get()
            ->unique('id');

        ChangelogEntry::query()->where('edition_id', $edition->id)->delete();
        Document::query()->forEdition($edition->id)->delete();
        Law::query()->forEdition($edition->id)->delete();

        foreach ($mediaAssets as $mediaAsset) {
            if (! $mediaAsset->contentNodes()->exists() && ! $mediaAsset->documentPages()->exists()) {
                $this->deleteStoredFileIfNeeded($mediaAsset->file_path);
                $this->deleteStoredFileIfNeeded($mediaAsset->thumbnail_path);
                $mediaAsset->delete();
            }
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function previewEdition(array $payload, ?Edition $targetEdition): Edition
    {
        if ($targetEdition) {
            return $targetEdition;
        }

        $editionData = Arr::get($payload, 'edition', []);
        $payloadCode = trim((string) ($editionData['code'] ?? ''));

        if ($payloadCode !== '') {
            $existingEdition = Edition::query()->where('code', $payloadCode)->first();

            if ($existingEdition) {
                return $existingEdition;
            }
        }

        return new Edition([
            'name' => (string) ($editionData['name'] ?? ($payloadCode !== '' ? $payloadCode : 'Dry Run Edition')),
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
     * @param array<int, string> $errors
     */
    protected function inspectImportTarget(Edition $edition, array $editionData, bool $replace, array &$warnings, array &$errors): void
    {
        $hasExistingContent = $edition->exists && ($edition->laws()->exists() || $edition->documents()->exists());

        if ($hasExistingContent && ! $replace) {
            $errors[] = 'Target edition already has content. Re-run the import with --replace to overwrite it.';
        }

        if ($hasExistingContent && $replace) {
            $warnings[] = 'Replace mode is enabled. Existing target edition content would be deleted before import.';
        }

        $desiredCode = trim((string) ($editionData['code'] ?? $edition->code ?? ''));
        $codeInUse = $desiredCode !== '' && Edition::query()
            ->where('code', $desiredCode)
            ->when($edition->exists, fn ($query) => $query->whereKeyNot($edition->id))
            ->exists();

        if ($codeInUse) {
            $warnings[] = 'Edition code '.$desiredCode.' is already in use, so the import would keep the current target edition code instead.';
        }

        $payloadStatus = (string) ($editionData['status'] ?? $edition->status ?? 'draft');

        if (! in_array($payloadStatus, ['draft', 'published'], true)) {
            $warnings[] = 'Edition status '.$payloadStatus.' is not supported and would be normalized to draft during import.';
        }

        if ($edition->is_active && $payloadStatus !== 'published') {
            $warnings[] = 'Target edition is active, so imported status would be forced to published.';
        }

        if (($editionData['is_active'] ?? false) && ! $edition->is_active) {
            $warnings[] = 'The JSON marks this edition as active, but import does not auto-activate editions.';
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, int>
     */
    protected function summarizePayload(array $payload): array
    {
        $counts = $this->emptyCounts();
        $counts['media_assets'] = is_array($payload['media_assets'] ?? null) ? count($payload['media_assets']) : 0;
        $counts['documents'] = is_array($payload['documents'] ?? null) ? count($payload['documents']) : 0;
        $counts['laws'] = is_array($payload['laws'] ?? null) ? count($payload['laws']) : 0;
        $counts['changelog_entries'] = is_array($payload['changelog_entries'] ?? null) ? count($payload['changelog_entries']) : 0;

        foreach (($payload['documents'] ?? []) as $documentPayload) {
            if (! is_array($documentPayload)) {
                continue;
            }

            $counts['document_pages'] += is_array($documentPayload['pages'] ?? null)
                ? count($documentPayload['pages'])
                : 0;
        }

        foreach (($payload['laws'] ?? []) as $lawPayload) {
            if (! is_array($lawPayload)) {
                continue;
            }

            $counts['qas'] += is_array($lawPayload['qas'] ?? null)
                ? count($lawPayload['qas'])
                : 0;
            $counts['qa_options'] += $this->countQaOptions($lawPayload['qas'] ?? []);
            $counts['nodes'] += $this->countNodeBranch($lawPayload['nodes'] ?? []);
        }

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    protected function emptyCounts(): array
    {
        return [
            'media_assets' => 0,
            'laws' => 0,
            'nodes' => 0,
            'qas' => 0,
            'qa_options' => 0,
            'documents' => 0,
            'document_pages' => 0,
            'changelog_entries' => 0,
        ];
    }

    /**
     * @param mixed $qas
     */
    protected function countQaOptions(mixed $qas): int
    {
        if (! is_array($qas)) {
            return 0;
        }

        $count = 0;

        foreach ($qas as $qaPayload) {
            if (! is_array($qaPayload) || ! is_array($qaPayload['options'] ?? null)) {
                continue;
            }

            $count += count($qaPayload['options']);
        }

        return $count;
    }

    /**
     * @param mixed $nodes
     */
    protected function countNodeBranch(mixed $nodes): int
    {
        if (! is_array($nodes)) {
            return 0;
        }

        $count = 0;

        foreach ($nodes as $nodePayload) {
            if (! is_array($nodePayload)) {
                continue;
            }

            $count++;
            $count += $this->countNodeBranch($nodePayload['children'] ?? []);
        }

        return $count;
    }

    /**
     * @param array<int, mixed> $mediaPayloads
     * @param array<int, string> $warnings
     * @param array<int, string> $errors
     * @return array<string, string>
     */
    protected function analyzeMediaAssets(array $mediaPayloads, array &$warnings, array &$errors): array
    {
        $knownMediaKeys = [];

        foreach ($mediaPayloads as $index => $mediaPayload) {
            $label = 'Media asset #'.($index + 1);

            if (! is_array($mediaPayload)) {
                $errors[] = $label.' must be an object.';

                continue;
            }

            $key = trim((string) ($mediaPayload['key'] ?? ''));

            if ($key === '') {
                $errors[] = $label.' is missing key.';

                continue;
            }

            if (isset($knownMediaKeys[$key])) {
                $errors[] = 'Duplicate media key detected in import payload: '.$key;

                continue;
            }

            $knownMediaKeys[$key] = (string) ($mediaPayload['asset_type'] ?? 'file');

            $storageType = (string) ($mediaPayload['storage_type'] ?? 'external');
            $filePath = (string) (($mediaPayload['file']['path'] ?? null) ?? ($mediaPayload['file_path'] ?? ''));
            $externalUrl = trim((string) ($mediaPayload['external_url'] ?? ''));

            if ($storageType === 'upload' && $filePath === '') {
                $errors[] = $label.' ('.$key.') uses upload storage but has no file path.';
            }

            if ($storageType !== 'upload' && $filePath === '' && $externalUrl === '') {
                $errors[] = $label.' ('.$key.') is missing both file_path and external_url.';
            }
        }

        return $knownMediaKeys;
    }

    /**
     * @param array<int, mixed> $documents
     * @param array<string, string> $knownMediaKeys
     * @param array<int, string> $warnings
     * @param array<int, string> $errors
     */
    protected function analyzeDocuments(array $documents, array $knownMediaKeys, array &$warnings, array &$errors): void
    {
        $seenSlugs = [];

        foreach ($documents as $index => $documentPayload) {
            $label = 'Document #'.($index + 1);

            if (! is_array($documentPayload)) {
                $errors[] = $label.' must be an object.';

                continue;
            }

            $slug = trim((string) ($documentPayload['slug'] ?? ''));

            if ($slug === '') {
                $warnings[] = $label.' is missing slug and would receive an auto-generated slug during import.';
            } elseif (isset($seenSlugs[$slug])) {
                $warnings[] = $label.' duplicates document slug '.$slug.' in this payload and would be auto-suffixed during import.';
            } else {
                $seenSlugs[$slug] = true;
            }

            $translations = $documentPayload['translations'] ?? [];

            if (! is_array($translations)) {
                $errors[] = $label.' translations must be an object keyed by language code.';
            } elseif (! $this->hasTranslationValue($translations, 'id', 'title')) {
                $warnings[] = $label.' is missing Indonesian title translation.';
            }

            $pages = $documentPayload['pages'] ?? [];

            if (! is_array($pages)) {
                $errors[] = $label.' pages must be an array.';

                continue;
            }

            $seenPageSlugs = [];

            foreach ($pages as $pageIndex => $pagePayload) {
                $pageLabel = $label.' page #'.($pageIndex + 1);

                if (! is_array($pagePayload)) {
                    $errors[] = $pageLabel.' must be an object.';

                    continue;
                }

                $pageSlug = trim((string) ($pagePayload['slug'] ?? ''));

                if ($pageSlug === '') {
                    $warnings[] = $pageLabel.' is missing slug and would receive a generated fallback during import.';
                } elseif (isset($seenPageSlugs[$pageSlug])) {
                    $warnings[] = $pageLabel.' duplicates page slug '.$pageSlug.' within the same document.';
                } else {
                    $seenPageSlugs[$pageSlug] = true;
                }

                $pageTranslations = $pagePayload['translations'] ?? [];

                if (! is_array($pageTranslations)) {
                    $errors[] = $pageLabel.' translations must be an object keyed by language code.';
                } elseif (! $this->hasTranslationValue($pageTranslations, 'id', 'title')) {
                    $warnings[] = $pageLabel.' is missing Indonesian title translation.';
                }

                $pageMedia = $pagePayload['media'] ?? [];

                if (! is_array($pageMedia)) {
                    $errors[] = $pageLabel.' media must be an array.';
                    continue;
                }

                $seenMediaKeys = [];
                $attachedMediaKeys = [];

                foreach ($pageMedia as $mediaIndex => $mediaReference) {
                    $mediaLabel = $pageLabel.' media #'.($mediaIndex + 1);

                    if (! is_array($mediaReference)) {
                        $errors[] = $mediaLabel.' must be an object.';
                        continue;
                    }

                    $mediaKey = trim((string) ($mediaReference['media_key'] ?? ''));
                    $assetKey = trim((string) ($mediaReference['asset_key'] ?? ''));

                    if ($mediaKey === '') {
                        $errors[] = $mediaLabel.' is missing media_key.';
                    } elseif (isset($seenMediaKeys[$mediaKey])) {
                        $errors[] = $mediaLabel.' duplicates media_key '.$mediaKey.' within the same document page.';
                    } else {
                        $seenMediaKeys[$mediaKey] = true;
                        $attachedMediaKeys[$mediaKey] = true;
                    }

                    if ($assetKey === '') {
                        $errors[] = $mediaLabel.' is missing asset_key.';
                    } elseif (! isset($knownMediaKeys[$assetKey])) {
                        $errors[] = $mediaLabel.' references missing media asset key '.$assetKey.'.';
                    } elseif ($knownMediaKeys[$assetKey] !== 'image') {
                        $errors[] = $mediaLabel.' must reference an image media asset.';
                    }
                }

                foreach ($this->documentMediaPlaceholders($pagePayload, $pageTranslations) as $placeholderKey) {
                    if (! isset($attachedMediaKeys[$placeholderKey])) {
                        $errors[] = $pageLabel.' contains placeholder {{media:'.$placeholderKey.'}} but no attached media uses that key.';
                    }
                }
            }
        }
    }

    /**
     * @param array<int, mixed> $laws
     * @param array<string, string> $knownMediaKeys
     * @param array<int, string> $warnings
     * @param array<int, string> $errors
     */
    protected function analyzeLaws(array $laws, array $knownMediaKeys, array &$warnings, array &$errors): void
    {
        $seenSlugs = [];

        foreach ($laws as $index => $lawPayload) {
            $label = 'Law #'.($index + 1);

            if (! is_array($lawPayload)) {
                $errors[] = $label.' must be an object.';

                continue;
            }

            $lawNumber = trim((string) ($lawPayload['law_number'] ?? ''));

            if ($lawNumber === '') {
                $warnings[] = $label.' is missing law_number and would be assigned sequentially during import.';
            }

            $slug = trim((string) ($lawPayload['slug'] ?? ''));

            if ($slug === '') {
                $warnings[] = $label.' is missing slug and would be generated from title data during import.';
            } elseif (isset($seenSlugs[$slug])) {
                $warnings[] = $label.' duplicates law slug '.$slug.' in this payload and would be auto-suffixed during import.';
            } else {
                $seenSlugs[$slug] = true;
            }

            $translations = $lawPayload['translations'] ?? [];

            if (! is_array($translations)) {
                $errors[] = $label.' translations must be an object keyed by language code.';
            } elseif (! $this->hasTranslationValue($translations, 'id', 'title')) {
                $warnings[] = $label.' is missing Indonesian title translation.';
            }

            $nodes = $lawPayload['nodes'] ?? [];

            if (! is_array($nodes)) {
                $errors[] = $label.' nodes must be an array.';
            } else {
                foreach ($nodes as $nodeIndex => $nodePayload) {
                    $this->analyzeNode(
                        $nodePayload,
                        $label.' node '.($nodeIndex + 1),
                        $knownMediaKeys,
                        $warnings,
                        $errors,
                    );
                }
            }

            $qas = $lawPayload['qas'] ?? [];

            if (! is_array($qas)) {
                $errors[] = $label.' qas must be an array.';

                continue;
            }

            foreach ($qas as $qaIndex => $qaPayload) {
                $qaLabel = $label.' Q&A #'.($qaIndex + 1);

                if (! is_array($qaPayload)) {
                    $errors[] = $qaLabel.' must be an object.';

                    continue;
                }

                $options = $qaPayload['options'] ?? [];
                $qaType = $this->qaTypeFromPayload($qaPayload);

                if (! in_array($qaType, [LawQa::TYPE_SIMPLE, LawQa::TYPE_MULTIPLE_CHOICE], true)) {
                    $errors[] = $qaLabel.' has unsupported qa_type '.$qaType.'.';
                }

                if (! is_array($options)) {
                    $errors[] = $qaLabel.' options must be an array when present.';
                    $options = [];
                }

                if ($qaType === LawQa::TYPE_MULTIPLE_CHOICE) {
                    $correctOptionCount = 0;

                    if (count($options) < 2) {
                        $errors[] = $qaLabel.' multiple_choice items need at least two options.';
                    }

                    foreach ($options as $optionIndex => $optionPayload) {
                        $optionLabel = $qaLabel.' option #'.($optionIndex + 1);

                        if (! is_array($optionPayload)) {
                            $errors[] = $optionLabel.' must be an object.';
                            continue;
                        }

                        if ((bool) ($optionPayload['is_correct'] ?? false)) {
                            $correctOptionCount++;
                        }

                        $optionTranslations = $optionPayload['translations'] ?? [];

                        if (! is_array($optionTranslations)) {
                            $errors[] = $optionLabel.' translations must be an object keyed by language code.';
                        } elseif (! $this->hasTranslationValue($optionTranslations, 'id', 'text')) {
                            $warnings[] = $optionLabel.' is missing Indonesian option text.';
                        }
                    }

                    if ($correctOptionCount < 1) {
                        $errors[] = $qaLabel.' multiple_choice items need at least one correct option.';
                    }
                }

                $qaTranslations = $qaPayload['translations'] ?? [];

                if (! is_array($qaTranslations)) {
                    $errors[] = $qaLabel.' translations must be an object keyed by language code.';
                    continue;
                }

                if (! $this->hasTranslationValue($qaTranslations, 'id', 'question')) {
                    $warnings[] = $qaLabel.' is missing Indonesian question translation.';
                }

                if (
                    ($qaType !== LawQa::TYPE_MULTIPLE_CHOICE || $this->qaUsesCustomAnswer($qaPayload))
                    && ! $this->hasTranslationValue($qaTranslations, 'id', 'answer_html')
                ) {
                    $warnings[] = $qaLabel.' is missing Indonesian answer translation.';
                }
            }
        }
    }

    /**
     * @param mixed $nodePayload
     * @param array<string, string> $knownMediaKeys
     * @param array<int, string> $warnings
     * @param array<int, string> $errors
     */
    protected function analyzeNode(mixed $nodePayload, string $label, array $knownMediaKeys, array &$warnings, array &$errors): void
    {
        if (! is_array($nodePayload)) {
            $errors[] = $label.' must be an object.';

            return;
        }

        $nodeType = (string) ($nodePayload['node_type'] ?? '');

        if (! in_array($nodeType, self::SUPPORTED_NODE_TYPES, true)) {
            $errors[] = $label.' has unsupported node_type '.$nodeType.'.';
        }

        $translations = $nodePayload['translations'] ?? [];

        if (! is_array($translations)) {
            $errors[] = $label.' translations must be an object keyed by language code.';
        } elseif (in_array($nodeType, ['section', 'rich_text'], true) && ! $this->hasAnyTranslationContent($translations, 'id')) {
            $warnings[] = $label.' is missing Indonesian translated content.';
        }

        $media = $nodePayload['media'] ?? [];

        if (! is_array($media)) {
            $errors[] = $label.' media must be an array.';
        } else {
            foreach ($media as $mediaIndex => $mediaReference) {
                if (! is_array($mediaReference)) {
                    $errors[] = $label.' media #'.($mediaIndex + 1).' must be an object.';

                    continue;
                }

                $mediaKey = trim((string) ($mediaReference['media_key'] ?? ''));

                if ($mediaKey === '') {
                    $errors[] = $label.' media #'.($mediaIndex + 1).' is missing media_key.';
                    continue;
                }

                if (! isset($knownMediaKeys[$mediaKey])) {
                    $errors[] = 'Missing media asset for key: '.$mediaKey.' referenced by '.$label.'.';
                }
            }
        }

        if (in_array($nodeType, ['image', 'video_group', 'resource_list'], true) && $media === []) {
            $warnings[] = $label.' is a '.$nodeType.' node with no attached media items.';
        }

        $children = $nodePayload['children'] ?? [];

        if (! is_array($children)) {
            $errors[] = $label.' children must be an array.';

            return;
        }

        foreach ($children as $childIndex => $childPayload) {
            $this->analyzeNode(
                $childPayload,
                $label.'.'.($childIndex + 1),
                $knownMediaKeys,
                $warnings,
                $errors,
            );
        }
    }

    /**
     * @param array<int, mixed> $entries
     * @param array<int, string> $warnings
     * @param array<int, string> $errors
     */
    protected function analyzeChangelogEntries(array $entries, array &$warnings, array &$errors): void
    {
        foreach ($entries as $index => $entryPayload) {
            $label = 'Changelog entry #'.($index + 1);

            if (! is_array($entryPayload)) {
                $errors[] = $label.' must be an object.';

                continue;
            }

            if (trim((string) ($entryPayload['title'] ?? '')) === '') {
                $warnings[] = $label.' is missing title.';
            }

            if (trim((string) ($entryPayload['language_code'] ?? '')) === '') {
                $warnings[] = $label.' is missing language_code and would default to id during import.';
            }
        }
    }

    /**
     * @param array<string, mixed> $pagePayload
     * @param mixed $translations
     * @return array<int, string>
     */
    protected function documentMediaPlaceholders(array $pagePayload, mixed $translations): array
    {
        $bodies = [
            Arr::get($pagePayload, 'base_body_html'),
        ];

        if (is_array($translations)) {
            foreach ($translations as $translationPayload) {
                if (is_array($translationPayload)) {
                    $bodies[] = Arr::get($translationPayload, 'body_html');
                }
            }
        }

        $placeholders = [];

        foreach ($bodies as $body) {
            if (! is_string($body) || $body === '') {
                continue;
            }

            if (preg_match_all('/\{\{\s*media:([A-Za-z0-9_-]+)\s*\}\}/', $body, $matches)) {
                $placeholders = array_merge($placeholders, $matches[1]);
            }
        }

        return array_values(array_unique($placeholders));
    }

    /**
     * @param mixed $translations
     */
    protected function hasTranslationValue(mixed $translations, string $languageCode, string $field): bool
    {
        if (! is_array($translations)) {
            return false;
        }

        $translation = $translations[$languageCode] ?? null;

        if (! is_array($translation)) {
            return false;
        }

        return filled($translation[$field] ?? null);
    }

    /**
     * @param mixed $translations
     */
    protected function hasAnyTranslationContent(mixed $translations, string $languageCode): bool
    {
        if (! is_array($translations)) {
            return false;
        }

        $translation = $translations[$languageCode] ?? null;

        if (! is_array($translation)) {
            return false;
        }

        return filled($translation['title'] ?? null) || filled($translation['body_html'] ?? null);
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
     * @param array<string, \App\Models\MediaAsset> $mediaKeyMap
     * @param array<string, int> $counts
     */
    protected function importDocument(Edition $edition, array $documentPayload, array $mediaKeyMap, array &$counts): void
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

            $mediaSyncPayload = [];

            foreach (Arr::get($pagePayload, 'media', []) as $mediaReference) {
                $mediaKey = trim((string) ($mediaReference['media_key'] ?? ''));
                $assetKey = trim((string) ($mediaReference['asset_key'] ?? ''));

                if ($mediaKey === '' || $assetKey === '' || ! isset($mediaKeyMap[$assetKey])) {
                    throw new InvalidArgumentException('Missing document page media asset for key: '.$assetKey);
                }

                $mediaSyncPayload[$mediaKeyMap[$assetKey]->id] = [
                    'media_key' => $mediaKey,
                    'sort_order' => (int) ($mediaReference['sort_order'] ?? 1),
                ];
            }

            if ($mediaSyncPayload !== []) {
                $page->mediaAssets()->sync($mediaSyncPayload);
            }
        }
    }

    /**
     * @param array<string, mixed> $qaPayload
     */
    protected function qaTypeFromPayload(array $qaPayload): string
    {
        $qaType = trim((string) ($qaPayload['qa_type'] ?? ''));

        if ($qaType !== '') {
            return $qaType;
        }

        return is_array($qaPayload['options'] ?? null) && count($qaPayload['options']) > 0
            ? LawQa::TYPE_MULTIPLE_CHOICE
            : LawQa::TYPE_SIMPLE;
    }

    /**
     * @param array<string, mixed> $qaPayload
     */
    protected function qaUsesCustomAnswer(array $qaPayload): bool
    {
        if (array_key_exists('uses_custom_answer', $qaPayload)) {
            return (bool) $qaPayload['uses_custom_answer'];
        }

        foreach (Arr::get($qaPayload, 'translations', []) as $translationPayload) {
            if (is_array($translationPayload) && filled($translationPayload['answer_html'] ?? null)) {
                return true;
            }
        }

        return false;
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
            $qaType = $this->qaTypeFromPayload($qaPayload);

            if (! in_array($qaType, [LawQa::TYPE_SIMPLE, LawQa::TYPE_MULTIPLE_CHOICE], true)) {
                throw new InvalidArgumentException('Unsupported Q&A type in import payload: '.$qaType);
            }

            $usesCustomAnswer = $qaType === LawQa::TYPE_MULTIPLE_CHOICE
                && $this->qaUsesCustomAnswer($qaPayload);

            $qa = LawQa::create([
                'law_id' => $law->id,
                'qa_type' => $qaType,
                'sort_order' => (int) ($qaPayload['sort_order'] ?? ($counts['qas'] + 1)),
                'is_published' => (bool) ($qaPayload['is_published'] ?? false),
                'uses_custom_answer' => $usesCustomAnswer,
            ]);

            $counts['qas']++;

            foreach (Arr::get($qaPayload, 'translations', []) as $languageCode => $translationPayload) {
                LawQaTranslation::create([
                    'law_qa_id' => $qa->id,
                    'language_code' => (string) $languageCode,
                    'question' => (string) ($translationPayload['question'] ?? 'Question'),
                    'answer_html' => $usesCustomAnswer || $qaType === LawQa::TYPE_SIMPLE
                        ? Arr::get($translationPayload, 'answer_html')
                        : null,
                    'status' => (string) ($translationPayload['status'] ?? 'draft'),
                ]);
            }

            foreach (Arr::get($qaPayload, 'options', []) as $optionPayload) {
                if (! is_array($optionPayload)) {
                    continue;
                }

                $option = LawQaOption::create([
                    'law_qa_id' => $qa->id,
                    'sort_order' => (int) ($optionPayload['sort_order'] ?? ($counts['qa_options'] + 1)),
                    'is_correct' => (bool) ($optionPayload['is_correct'] ?? false),
                ]);

                $counts['qa_options']++;

                foreach (Arr::get($optionPayload, 'translations', []) as $languageCode => $translationPayload) {
                    LawQaOptionTranslation::create([
                        'option_id' => $option->id,
                        'language_code' => (string) $languageCode,
                        'text' => (string) ($translationPayload['text'] ?? ''),
                    ]);
                }
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

        if (! in_array($nodeType, self::SUPPORTED_NODE_TYPES, true)) {
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

        if (trim((string) Arr::get($payload, 'edition.code', '')) === '') {
            throw new InvalidArgumentException('Edition import payload must include a non-empty edition.code value.');
        }
    }
}
