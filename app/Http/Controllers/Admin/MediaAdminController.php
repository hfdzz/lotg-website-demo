<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Services\LotgPublicCache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MediaAdminController extends Controller
{
    public function __construct(
        protected LotgPublicCache $publicCache
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', MediaAsset::class);

        return view('admin.media.index', [
            'mediaAssets' => $this->mediaLibraryQuery()
                ->withCount(['contentNodes', 'documentPages'])
                ->orderByRaw("case when asset_type = 'image' then 1 else 2 end")
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', MediaAsset::class);

        $validated = $this->validateMedia($request);

        $mediaAsset = MediaAsset::create($this->payloadFromRequest($request, $validated));

        return redirect()
            ->route('admin.media.edit', ['media' => $mediaAsset])
            ->with('status', 'Media created.');
    }

    public function edit(MediaAsset $media): View
    {
        $this->authorize('update', $media);
        $this->assertLibraryMedia($media);

        $media->loadCount(['contentNodes', 'documentPages']);
        $media->load([
            'contentNodes' => fn ($query) => $query
                ->with(['law.edition', 'law.translations', 'translations'])
                ->orderBy('law_id')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'documentPages' => fn ($query) => $query
                ->with(['document.edition', 'translations'])
                ->orderBy('document_id')
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        return view('admin.media.edit', [
            'media' => $media,
        ]);
    }

    public function update(Request $request, MediaAsset $media): RedirectResponse
    {
        $this->authorize('update', $media);
        $this->assertLibraryMedia($media);

        $validated = $this->validateMedia($request, $media);
        $oldFilePath = $media->file_path;
        $oldStorageType = $media->storage_type;
        $oldStorageDisk = $media->storage_disk;
        $oldThumbnailPath = $media->thumbnail_path;
        $relatedLawIds = $media->contentNodes()->pluck('content_nodes.law_id')->map(fn ($id) => (int) $id)->unique()->all();

        $media->update($this->payloadFromRequest($request, $validated, $media));

        if ($oldStorageType === 'upload' && $oldFilePath && (
            $media->storage_type !== 'upload'
            || $oldFilePath !== $media->file_path
            || ($oldStorageDisk ?: 'public') !== ($media->storage_disk ?: 'public')
        )) {
            $this->deleteStoredFileIfNeeded($oldFilePath, $oldStorageDisk);
        }

        if ($oldThumbnailPath && $oldThumbnailPath !== $media->thumbnail_path) {
            $this->deleteStoredFileIfNeeded($oldThumbnailPath, $oldStorageDisk);
        }

        $this->publicCache->touchLaws($relatedLawIds);

        return redirect()
            ->route('admin.media.edit', ['media' => $media])
            ->with('status', 'Media updated.');
    }

    public function destroy(MediaAsset $media): RedirectResponse
    {
        $this->authorize('delete', $media);
        $this->assertLibraryMedia($media);

        if ($media->contentNodes()->exists() || $media->documentPages()->exists()) {
            return back()->withErrors([
                'media' => 'This media is still attached to one or more nodes or document pages. Remove those links before deleting it.',
            ]);
        }

        $this->deleteStoredFileIfNeeded($media->file_path, $media->storage_disk);
        $this->deleteStoredFileIfNeeded($media->thumbnail_path, $media->storage_disk);
        $media->delete();

        return redirect()
            ->route('admin.media.index')
            ->with('status', 'Media deleted.');
    }

    protected function validateMedia(Request $request, ?MediaAsset $media = null): array
    {
        $assetType = $media?->asset_type ?: (string) $request->input('asset_type');
        $videoSource = (string) $request->input('video_source', $media?->storage_type === 'upload' ? 'upload' : 'youtube');
        $uploadDiskOptions = $this->availableUploadDisks();

        $validator = Validator::make($request->all(), [
            'asset_type' => [$media ? 'nullable' : 'required', 'in:image,video'],
            'image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,avif,svg', 'max:5120'],
            'video_file' => ['nullable', 'file', 'mimes:mp4', 'max:'.$this->videoUploadMaxKb()],
            'video_source' => ['nullable', 'in:upload,youtube'],
            'upload_disk' => ['nullable', Rule::in($uploadDiskOptions)],
            'external_url' => ['nullable', 'url'],
            'caption' => ['nullable', 'string'],
            'credit' => ['nullable', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($request, $assetType, $media, $videoSource): void {
            if ($assetType === 'image' && ! $request->hasFile('image_file') && ! $media?->file_path) {
                $validator->errors()->add('image_file', 'An image file is required for image media.');
            }

            if ($assetType === 'video') {
                if ($videoSource === 'upload') {
                    $hasExistingUpload = $media?->asset_type === 'video'
                        && $media?->storage_type === 'upload'
                        && filled($media?->file_path);

                    if (! $request->hasFile('video_file') && ! $hasExistingUpload) {
                        $validator->errors()->add('video_file', 'An MP4 video file is required for uploaded video media.');
                    }

                    return;
                }

                $url = trim((string) $request->input('external_url'));

                if ($url === '') {
                    $validator->errors()->add('external_url', 'A YouTube URL is required for YouTube video media.');

                    return;
                }

                if (! MediaAsset::parseYouTubeId($url)) {
                    $validator->errors()->add('external_url', 'YouTube video media currently supports YouTube URLs only.');
                }
            }
        });

        return $validator->validate();
    }

    protected function payloadFromRequest(Request $request, array $validated, ?MediaAsset $media = null): array
    {
        $assetType = $media?->asset_type ?: $validated['asset_type'];
        $payload = [
            'asset_type' => $assetType,
            'is_library_item' => true,
            'caption' => $validated['caption'] ?: null,
            'credit' => $validated['credit'] ?: null,
        ];

        if ($assetType === 'image') {
            $imageDisk = $media?->storage_disk ?: 'public';
            $payload['storage_type'] = 'upload';
            $payload['storage_disk'] = $imageDisk;
            $payload['external_url'] = null;
            $payload['file_path'] = $request->hasFile('image_file')
                ? $request->file('image_file')->store('lotg-media/images', $imageDisk)
                : $media?->file_path;
        }

        if ($assetType === 'video') {
            $videoSource = (string) ($validated['video_source'] ?? 'youtube');

            if ($videoSource === 'upload') {
                $selectedDisk = $request->hasFile('video_file')
                    ? $this->selectedUploadDisk($validated)
                    : ($media?->storage_disk ?: $this->defaultUploadDisk());

                $payload['storage_type'] = 'upload';
                $payload['storage_disk'] = $selectedDisk;
                $payload['file_path'] = $request->hasFile('video_file')
                    ? $request->file('video_file')->store('lotg-media/videos', $selectedDisk)
                    : $media?->file_path;
                $payload['external_url'] = null;
            } else {
                $payload['storage_type'] = 'youtube';
                $payload['storage_disk'] = null;
                $payload['file_path'] = null;
                $payload['external_url'] = trim((string) ($validated['external_url'] ?? ''));
            }
        }

        return $payload;
    }

    protected function deleteStoredFileIfNeeded(?string $filePath, ?string $storageDisk = null): void
    {
        if (! $filePath || str_starts_with($filePath, 'demo/')) {
            return;
        }

        if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
            return;
        }

        $disk = $storageDisk ?: $this->defaultUploadDisk();

        if (Storage::disk($disk)->exists($filePath)) {
            Storage::disk($disk)->delete($filePath);
        }
    }

    protected function mediaLibraryQuery()
    {
        return MediaAsset::query()
            ->libraryItems()
            ->whereIn('asset_type', ['image', 'video']);
    }

    protected function assertLibraryMedia(MediaAsset $media): void
    {
        abort_unless($media->is_library_item && in_array($media->asset_type, ['image', 'video'], true), 404);
    }

    /**
     * @return array<int, string>
     */
    protected function availableUploadDisks(): array
    {
        $configured = collect(config('lotg.media_upload_disks', ['public', 's3']))
            ->map(fn ($disk) => trim((string) $disk))
            ->filter(fn (string $disk) => $disk !== '' && config('filesystems.disks.'.$disk))
            ->unique()
            ->values()
            ->all();

        return $configured !== [] ? $configured : ['public'];
    }

    protected function defaultUploadDisk(): string
    {
        $configuredDefault = trim((string) config('lotg.media_default_upload_disk', 'public'));

        return in_array($configuredDefault, $this->availableUploadDisks(), true)
            ? $configuredDefault
            : $this->availableUploadDisks()[0];
    }

    protected function selectedUploadDisk(array $validated): string
    {
        $selectedDisk = trim((string) ($validated['upload_disk'] ?? ''));

        if ($selectedDisk !== '' && in_array($selectedDisk, $this->availableUploadDisks(), true)) {
            return $selectedDisk;
        }

        return $this->defaultUploadDisk();
    }

    protected function videoUploadMaxKb(): int
    {
        return max((int) config('lotg.video_upload_max_kb', 51200), 1);
    }
}
