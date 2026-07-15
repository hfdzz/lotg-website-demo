<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Services\EditionJsonExporter;
use App\Services\EditionJsonImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditionTransferAdminController extends Controller
{
    public function __construct(
        protected EditionJsonExporter $exporter,
        protected EditionJsonImporter $importer
    ) {
    }

    public function export(Edition $edition): StreamedResponse
    {
        $this->authorize('view', $edition);

        $payload = $this->exporter->export($edition);
        $filename = $this->exporter->defaultFilename($edition);
        $json = $this->exporter->encodePayload($payload);

        return response()->streamDownload(function () use ($json): void {
            echo $json;
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function storeExport(Request $request, Edition $edition): RedirectResponse
    {
        $this->authorize('view', $edition);

        $validated = $request->validate([
            'export_disk' => ['required', 'string', Rule::in($this->availableExportDisks())],
            'export_path' => ['nullable', 'string', 'max:1024'],
        ]);

        try {
            $payload = $this->exporter->export($edition);
            $warnings = $this->exporter->exportWarnings();
            $json = $this->exporter->encodePayload($payload);
            $disk = (string) $validated['export_disk'];
            $path = $this->normalizeDiskExportPath($validated['export_path'] ?? null, $edition);

            $stored = Storage::disk($disk)->put($path, $json);

            if (! $stored) {
                throw new \RuntimeException('Failed to write export JSON to '.$disk.'://'.$path);
            }

            return redirect()
                ->route('admin.editions.index', ['edition' => $edition->id])
                ->with('status', 'Edition export saved to '.$disk.'://'.$path)
                ->with('edition_transfer_report', [
                    'title' => 'Export summary',
                    'mode' => 'export-disk',
                    'edition_label' => $this->editionLabel($edition),
                    'destination' => $disk.'://'.$path,
                    'counts' => $this->summarizeExportPayload($payload),
                    'warnings' => $warnings,
                    'errors' => [],
                ]);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('admin.editions.index', ['edition' => $edition->id])
                ->withErrors(['edition_export' => $exception->getMessage()])
                ->withInput();
        }
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Edition::class);

        $validated = $request->validate([
            'import_mode' => ['nullable', 'string'],
            'import_file' => ['required', 'file'],
            'target_edition_id' => ['nullable', 'integer', 'exists:editions,id'],
            'replace' => ['nullable', 'boolean'],
            'dry_run' => ['nullable', 'boolean'],
        ]);

        $targetEdition = null;

        if (! empty($validated['target_edition_id'])) {
            $targetEdition = Edition::query()->find((int) $validated['target_edition_id']);

            if ($targetEdition) {
                $this->authorize('update', $targetEdition);
            }
        }

        try {
            $payload = $this->decodeUploadedJson($request->file('import_file'));
            $replace = $request->boolean('replace');
            $isDryRun = $request->boolean('dry_run');

            $result = $isDryRun
                ? $this->importer->dryRun($payload, $targetEdition, $replace)
                : $this->importer->import($payload, $targetEdition, $replace);

            $selectedEditionId = $result['edition']->exists
                ? $result['edition']->id
                : ($targetEdition?->id);

            $redirect = redirect()
                ->route('admin.editions.index', array_filter([
                    'edition' => $selectedEditionId,
                ]))
                ->with('edition_transfer_report', [
                    'title' => $isDryRun ? 'Import dry run summary' : 'Import summary',
                    'mode' => $isDryRun ? 'dry-run' : 'import',
                    'edition_label' => $this->editionLabel($result['edition']),
                    'counts' => $result['counts'],
                    'warnings' => $result['warnings'] ?? [],
                    'errors' => $result['errors'] ?? [],
                    'can_import' => $result['can_import'] ?? true,
                    'replace' => $replace,
                ]);

            if (! $isDryRun || (($result['can_import'] ?? true) && empty($result['errors'] ?? []))) {
                $redirect->with('status', $isDryRun ? 'Edition import dry run completed.' : 'Edition import completed.');
            }

            return $redirect;
        } catch (\Throwable $exception) {
            return redirect()
                ->route('admin.editions.index', array_filter([
                    'edition' => $targetEdition?->id,
                ]))
                ->withErrors(['edition_import' => $exception->getMessage()])
                ->withInput();
        }
    }

    protected function decodeUploadedJson(?UploadedFile $file): array
    {
        if (! $file || ! $file->isValid()) {
            throw new \InvalidArgumentException('Upload a valid JSON file before importing.');
        }

        $path = $file->getRealPath();

        if (! $path) {
            throw new \InvalidArgumentException('The uploaded JSON file could not be read.');
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \InvalidArgumentException('The uploaded JSON file could not be read.');
        }

        $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($payload)) {
            throw new \InvalidArgumentException('The uploaded JSON must decode to an object payload.');
        }

        return $payload;
    }

    protected function editionLabel(Edition $edition): string
    {
        $code = trim((string) $edition->code);

        return $code !== ''
            ? $edition->name.' ('.$code.')'
            : $edition->name;
    }

    /**
     * @return array<int, string>
     */
    protected function availableExportDisks(): array
    {
        return collect(config('lotg.export_disks', ['local', 's3']))
            ->map(fn ($disk) => trim((string) $disk))
            ->filter(fn (string $disk) => $disk !== '' && config('filesystems.disks.'.$disk))
            ->values()
            ->all();
    }

    protected function normalizeDiskExportPath(?string $path, Edition $edition): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim((string) $path)), '/');

        return $normalized !== ''
            ? $normalized
            : $this->exporter->defaultDiskPath($edition);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, int>
     */
    protected function summarizeExportPayload(array $payload): array
    {
        $laws = collect($payload['laws'] ?? []);
        $documents = collect($payload['documents'] ?? []);

        return [
            'media_assets' => count($payload['media_assets'] ?? []),
            'laws' => $laws->count(),
            'nodes' => $laws->sum(fn (array $lawPayload) => $this->countNodePayloads($lawPayload['nodes'] ?? [])),
            'qas' => $laws->sum(fn (array $lawPayload) => count($lawPayload['qas'] ?? [])),
            'qa_options' => $laws->sum(
                fn (array $lawPayload) => collect($lawPayload['qas'] ?? [])
                    ->sum(fn (array $qaPayload) => count($qaPayload['options'] ?? []))
            ),
            'documents' => $documents->count(),
            'document_pages' => $documents->sum(fn (array $documentPayload) => count($documentPayload['pages'] ?? [])),
            'changelog_entries' => count($payload['changelog_entries'] ?? []),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     */
    protected function countNodePayloads(array $nodes): int
    {
        return collect($nodes)->sum(function (array $node): int {
            return 1 + $this->countNodePayloads($node['children'] ?? []);
        });
    }
}
