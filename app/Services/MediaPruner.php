<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MediaPruner
{
    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\MediaAsset>
     */
    public function orphanedMediaAssets(bool $onlyNonLibrary = false, ?string $disk = null): Collection
    {
        return MediaAsset::query()
            ->when($onlyNonLibrary, fn ($query) => $query->where('is_library_item', false))
            ->when($disk, fn ($query) => $query->where('storage_disk', $disk))
            ->whereDoesntHave('contentNodes')
            ->whereDoesntHave('documentPages')
            ->orderBy('asset_type')
            ->orderBy('storage_disk')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{count: int, assets: \Illuminate\Support\Collection<int, \App\Models\MediaAsset>}
     */
    public function prune(bool $onlyNonLibrary = false, ?string $disk = null): array
    {
        $assets = $this->orphanedMediaAssets($onlyNonLibrary, $disk);

        foreach ($assets as $asset) {
            $this->deleteStoredFileIfNeeded($asset->file_path, $asset->storage_disk);
            $this->deleteStoredFileIfNeeded($asset->thumbnail_path, $asset->storage_disk);
            $asset->delete();
        }

        return [
            'count' => $assets->count(),
            'assets' => $assets,
        ];
    }

    protected function deleteStoredFileIfNeeded(?string $path, ?string $disk = null): void
    {
        if (! $path || str_starts_with($path, 'demo/')) {
            return;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $resolvedDisk = $disk ?: config('lotg.media_default_upload_disk', 'public');

        if (Storage::disk($resolvedDisk)->exists($path)) {
            Storage::disk($resolvedDisk)->delete($path);
        }
    }
}
