<?php

use App\Models\Edition;
use App\Services\EditionJsonExporter;
use App\Services\EditionJsonImporter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lotg:edition-export {edition : Edition id or code} {path? : Output JSON path}', function (
    EditionJsonExporter $exporter,
    string $edition,
    ?string $path = null
) {
    $editionModel = Edition::query()
        ->when(
            is_numeric($edition),
            fn ($query) => $query->whereKey((int) $edition),
            fn ($query) => $query->where('code', $edition)
        )
        ->first();

    if (! $editionModel) {
        $this->error('Edition not found: '.$edition);

        return Command::FAILURE;
    }

    $exportPath = $path
        ? lotg_console_path($path)
        : lotg_console_default_export_path('lotg-edition-'.$editionModel->code.'-'.now()->format('Ymd_His').'.json');

    File::ensureDirectoryExists(dirname($exportPath));

    $payload = $exporter->export($editionModel);

    try {
        File::put(
            $exportPath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );
    } catch (\Throwable $exception) {
        $this->error($exception->getMessage());

        return Command::FAILURE;
    }

    $this->info('Edition exported to '.$exportPath);
    $this->line('Laws: '.count($payload['laws']));
    $this->line('Documents: '.count($payload['documents']));
    $this->line('Changelog entries: '.count($payload['changelog_entries'] ?? []));
    $this->line('Media assets: '.count($payload['media_assets']));

    return Command::SUCCESS;
})->purpose('Export one LotG edition to JSON');

Artisan::command('lotg:edition-import {path : Path to edition JSON file} {--edition= : Existing edition id or code to import into} {--replace : Replace existing content in the target edition}', function (
    EditionJsonImporter $importer,
    string $path
) {
    $importPath = lotg_console_path($path);

    if (! File::exists($importPath)) {
        $this->error('Import file not found: '.$importPath);

        return Command::FAILURE;
    }

    try {
        $payload = json_decode(File::get($importPath), true, 512, JSON_THROW_ON_ERROR);
    } catch (\Throwable $exception) {
        $this->error($exception->getMessage());

        return Command::FAILURE;
    }

    $targetEdition = null;
    $editionOption = $this->option('edition');

    if (filled($editionOption)) {
        $targetEdition = Edition::query()
            ->when(
                is_numeric((string) $editionOption),
                fn ($query) => $query->whereKey((int) $editionOption),
                fn ($query) => $query->where('code', (string) $editionOption)
            )
            ->first();

        if (! $targetEdition) {
            $this->error('Target edition not found: '.$editionOption);

            return Command::FAILURE;
        }
    }

    try {
        $result = $importer->import(
            $payload,
            $targetEdition,
            (bool) $this->option('replace'),
        );
    } catch (\Throwable $exception) {
        $this->error($exception->getMessage());

        return Command::FAILURE;
    }

    $this->info('Edition import completed for '.$result['edition']->name.' ('.$result['edition']->code.').');
    $this->line('Laws: '.$result['counts']['laws']);
    $this->line('Nodes: '.$result['counts']['nodes']);
    $this->line('Q&A: '.$result['counts']['qas']);
    $this->line('Documents: '.$result['counts']['documents']);
    $this->line('Document pages: '.$result['counts']['document_pages']);
    $this->line('Changelog entries: '.$result['counts']['changelog_entries']);
    $this->line('Media assets: '.$result['counts']['media_assets']);

    foreach ($result['warnings'] as $warning) {
        $this->warn($warning);
    }

    return Command::SUCCESS;
})->purpose('Import a LotG edition JSON file into an edition');

if (! function_exists('lotg_console_path')) {
    function lotg_console_path(string $path): string
    {
        if (Str::startsWith($path, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            return $path;
        }

        return base_path($path);
    }
}

if (! function_exists('lotg_console_default_export_path')) {
    function lotg_console_default_export_path(string $filename): string
    {
        $directory = rtrim((string) config('lotg.export_default_dir', 'storage/app/exports'), '/\\');

        return lotg_console_path($directory.DIRECTORY_SEPARATOR.$filename);
    }
}
