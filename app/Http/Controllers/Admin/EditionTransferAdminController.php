<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Services\EditionJsonExporter;
use App\Services\EditionJsonImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
        $filename = 'lotg-edition-'.$edition->code.'-'.now()->format('Ymd_His').'.json';
        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        return response()->streamDownload(function () use ($json): void {
            echo $json;
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
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
}
