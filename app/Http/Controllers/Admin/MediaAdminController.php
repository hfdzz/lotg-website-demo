<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\PendingMediaUpload;
use App\Services\LotgPublicCache;
use App\Services\PendingMediaUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class MediaAdminController extends Controller
{
    public function __construct(
        protected LotgPublicCache $publicCache,
        protected PendingMediaUploadService $pendingUploads
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', MediaAsset::class);

        $selectedMediaType = in_array((string) $request->query('media_type', 'all'), ['all', 'image', 'video'], true)
            ? (string) $request->query('media_type', 'all')
            : 'all';
        $selectedUsageFilter = in_array((string) $request->query('usage_filter', 'all'), ['all', 'any', 'published', 'active', 'no'], true)
            ? (string) $request->query('usage_filter', 'all')
            : 'all';

        $mediaQuery = $this->mediaLibraryQuery();

        if ($selectedMediaType !== 'all') {
            $mediaQuery->where('asset_type', $selectedMediaType);
        }

        match ($selectedUsageFilter) {
            'any' => $mediaQuery->where(function ($query): void {
                $query->whereHas('contentNodes')
                    ->orWhereHas('documentPages');
            }),
            'published' => $mediaQuery->whereHas('contentNodes', fn ($query) => $query->where('content_nodes.is_published', true)),
            'active' => $mediaQuery->whereHas('contentNodes.law.edition', fn ($query) => $query->active()),
            'no' => $mediaQuery
                ->whereDoesntHave('contentNodes')
                ->whereDoesntHave('documentPages'),
            default => null,
        };

        return view('admin.media.index', [
            'mediaAssets' => $mediaQuery
                ->withCount([
                    'contentNodes',
                    'documentPages',
                    'contentNodes as published_content_nodes_count' => fn ($query) => $query->where('content_nodes.is_published', true),
                    'contentNodes as active_edition_content_nodes_count' => fn ($query) => $query->whereHas('law.edition', fn ($editionQuery) => $editionQuery->active()),
                    'contentNodes as published_active_edition_content_nodes_count' => fn ($query) => $query
                        ->where('content_nodes.is_published', true)
                        ->whereHas('law.edition', fn ($editionQuery) => $editionQuery->active()),
                ])
                ->orderByRaw("case when asset_type = 'image' then 1 else 2 end")
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get(),
            'selectedMediaType' => $selectedMediaType,
            'selectedUsageFilter' => $selectedUsageFilter,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', MediaAsset::class);

        if (is_array($request->input('items'))) {
            $bulkItems = $this->normalizedBulkItems($request);

            if ($bulkItems === []) {
                return back()
                    ->withErrors(['media' => 'Add at least one media item before creating media.'])
                    ->withInput();
            }

            $createdCount = 0;

            DB::transaction(function () use ($request, $bulkItems, &$createdCount): void {
                foreach ($bulkItems as $index => $itemData) {
                    $itemRequest = $this->mediaItemRequest($request, (int) $index, $itemData);

                    try {
                        $validated = $this->validateMedia($itemRequest);
                    } catch (ValidationException $exception) {
                        throw $this->validationExceptionForBulkItem($exception, (int) $index);
                    }

                    MediaAsset::create($this->payloadFromRequest($itemRequest, $validated));
                    $createdCount++;
                }
            });

            return redirect()
                ->route('admin.media.index')
                ->with('status', $createdCount.' '.Str::plural('media', $createdCount).' created.');
        }

        $validated = $this->validateMedia($request);

        $mediaAsset = MediaAsset::create($this->payloadFromRequest($request, $validated));

        return redirect()
            ->route('admin.media.edit', ['media' => $mediaAsset])
            ->with('status', 'Media created.');
    }

    public function upload(Request $request): JsonResponse
    {
        $this->authorize('create', MediaAsset::class);

        $validated = Validator::make($request->all(), [
            'file' => ['required', 'file'],
            'upload_disk' => ['nullable', Rule::in($this->availableUploadDisks())],
        ])->validate();

        $file = $request->file('file');

        if (! $file) {
            throw ValidationException::withMessages([
                'file' => 'A file upload is required.',
            ]);
        }

        $assetType = $this->pendingUploads->detectUploadedFileType($file);

        if (! in_array($assetType, ['image', 'video'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Only image files or MP4 videos are supported.',
            ]);
        }

        Validator::make(
            ['file' => $file],
            [
                'file' => $assetType === 'image'
                    ? ['file', 'mimes:jpg,jpeg,png,gif,webp,avif,svg', 'max:5120']
                    : ['file', 'mimes:mp4', 'max:'.$this->videoUploadMaxKb()],
            ]
        )->validate();

        $upload = $this->pendingUploads->create(
            $file,
            (int) $request->user()->id,
            $this->selectedUploadDisk($validated)
        );

        return response()->json([
            'token' => $upload->uuid,
            'asset_type' => $upload->asset_type,
            'storage_disk' => $upload->storage_disk,
            'original_name' => $upload->original_name,
            'size_bytes' => $upload->size_bytes,
        ]);
    }

    public function destroyUpload(Request $request, PendingMediaUpload $upload): JsonResponse
    {
        $this->authorize('create', MediaAsset::class);
        abort_unless((int) $upload->user_id === (int) $request->user()->id, 404);

        $this->pendingUploads->delete($upload);

        return response()->json([
            'deleted' => true,
        ]);
    }

    public function edit(MediaAsset $media): View
    {
        $this->authorize('update', $media);
        $this->assertLibraryMedia($media);

        $media->loadCount([
            'contentNodes',
            'documentPages',
            'contentNodes as published_content_nodes_count' => fn ($query) => $query->where('content_nodes.is_published', true),
            'contentNodes as active_edition_content_nodes_count' => fn ($query) => $query->whereHas('law.edition', fn ($editionQuery) => $editionQuery->active()),
            'contentNodes as published_active_edition_content_nodes_count' => fn ($query) => $query
                ->where('content_nodes.is_published', true)
                ->whereHas('law.edition', fn ($editionQuery) => $editionQuery->active()),
        ]);
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
            'upload_token' => ['nullable', 'string'],
            'external_url' => ['nullable', 'url'],
            'caption' => ['nullable', 'string'],
            'credit' => ['nullable', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($request, $assetType, $media, $videoSource): void {
            $pendingUpload = $this->pendingUploadForRequest($request);

            if ($pendingUpload && $pendingUpload->asset_type !== $assetType) {
                $validator->errors()->add('upload_token', 'The uploaded file does not match the selected media type.');
            }

            if ($assetType === 'image' && ! $request->hasFile('image_file') && ! $pendingUpload && ! $media?->file_path) {
                $validator->errors()->add('image_file', 'An image file is required for image media.');
            }

            if ($assetType === 'video') {
                if ($videoSource === 'upload') {
                    $hasExistingUpload = $media?->asset_type === 'video'
                        && $media?->storage_type === 'upload'
                        && filled($media?->file_path);

                    if (! $request->hasFile('video_file') && ! $pendingUpload && ! $hasExistingUpload) {
                        $validator->errors()->add('video_file', 'An MP4 video file is required for uploaded video media.');
                    }

                    return;
                }

                if ($pendingUpload) {
                    $validator->errors()->add('upload_token', 'Uploaded video files require "Uploaded file" as the selected video source.');
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
            $imageDisk = ($request->hasFile('image_file') || $this->pendingUploadForRequest($request))
                ? $this->selectedUploadDisk($validated)
                : ($media?->storage_disk ?: $this->defaultUploadDisk());
            $payload['storage_type'] = 'upload';
            $payload['storage_disk'] = $imageDisk;
            $payload['external_url'] = null;
            $payload['file_path'] = $request->hasFile('image_file')
                ? $request->file('image_file')->store('lotg-media/images', $imageDisk)
                : ($this->pendingUploadForRequest($request)
                    ? $this->pendingUploads->promote($this->pendingUploadForRequest($request), $imageDisk)
                    : $media?->file_path);
        }

        if ($assetType === 'video') {
            $videoSource = (string) ($validated['video_source'] ?? 'youtube');

            if ($videoSource === 'upload') {
                $selectedDisk = ($request->hasFile('video_file') || $this->pendingUploadForRequest($request))
                    ? $this->selectedUploadDisk($validated)
                    : ($media?->storage_disk ?: $this->defaultUploadDisk());

                $payload['storage_type'] = 'upload';
                $payload['storage_disk'] = $selectedDisk;
                $payload['file_path'] = $request->hasFile('video_file')
                    ? $request->file('video_file')->store('lotg-media/videos', $selectedDisk)
                    : ($this->pendingUploadForRequest($request)
                        ? $this->pendingUploads->promote($this->pendingUploadForRequest($request), $selectedDisk)
                        : $media?->file_path);
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

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function normalizedBulkItems(Request $request): array
    {
        $items = $request->input('items');

        if (! is_array($items)) {
            return [];
        }

        $files = $request->allFiles();
        $normalized = [];

        foreach ($items as $index => $itemData) {
            if (! is_array($itemData)) {
                continue;
            }

            $itemFiles = data_get($files, 'items.'.$index, []);

            if (! $this->bulkItemHasInput($itemData, is_array($itemFiles) ? $itemFiles : [])) {
                continue;
            }

            $normalized[(int) $index] = $itemData;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $itemData
     * @param  array<string, mixed>  $itemFiles
     */
    protected function bulkItemHasInput(array $itemData, array $itemFiles): bool
    {
        if (
            data_get($itemFiles, 'image_file')
            || data_get($itemFiles, 'video_file')
        ) {
            return true;
        }

        if (trim((string) ($itemData['upload_token'] ?? '')) !== '') {
            return true;
        }

        if (trim((string) ($itemData['external_url'] ?? '')) !== '') {
            return true;
        }

        if (trim((string) ($itemData['caption'] ?? '')) !== '' || trim((string) ($itemData['credit'] ?? '')) !== '') {
            return true;
        }

        return ($itemData['asset_type'] ?? 'image') === 'video';
    }

    /**
     * @param  array<string, mixed>  $itemData
     */
    protected function mediaItemRequest(Request $request, int $index, array $itemData): Request
    {
        $itemFiles = data_get($request->allFiles(), 'items.'.$index, []);
        $server = $request->server->all();
        $server['REQUEST_METHOD'] = 'POST';
        $itemRequest = Request::create(
            $request->url(),
            'POST',
            $itemData,
            [],
            is_array($itemFiles) ? $itemFiles : [],
            $server
        );

        $itemRequest->setUserResolver(fn () => $request->user());

        return $itemRequest;
    }

    protected function validationExceptionForBulkItem(ValidationException $exception, int $index): ValidationException
    {
        $prefixedErrors = [];

        foreach ($exception->errors() as $field => $messages) {
            $prefixedErrors['items.'.$index.'.'.$field] = array_map(
                fn (string $message) => 'Media item '.($index + 1).': '.$message,
                $messages
            );
        }

        return ValidationException::withMessages($prefixedErrors);
    }

    protected function pendingUploadForRequest(Request $request): ?PendingMediaUpload
    {
        if ($request->attributes->has('pending_upload_resolved')) {
            return $request->attributes->get('pending_upload');
        }

        $request->attributes->set('pending_upload_resolved', true);

        $token = trim((string) $request->input('upload_token', ''));

        if ($token === '' || ! $request->user()) {
            $request->attributes->set('pending_upload', null);

            return null;
        }

        $upload = PendingMediaUpload::query()
            ->where('uuid', $token)
            ->where('user_id', $request->user()->id)
            ->first();

        $request->attributes->set('pending_upload', $upload);

        return $upload;
    }
}
