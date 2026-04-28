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
        $relatedLawIds = $media->contentNodes()->pluck('content_nodes.law_id')->map(fn ($id) => (int) $id)->unique()->all();

        $media->update($this->payloadFromRequest($request, $validated, $media));

        if (
            $media->asset_type === 'image'
            && $oldFilePath
            && $oldFilePath !== $media->file_path
        ) {
            $this->deleteStoredFileIfNeeded($oldFilePath);
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

        $this->deleteStoredFileIfNeeded($media->file_path);
        $media->delete();

        return redirect()
            ->route('admin.media.index')
            ->with('status', 'Media deleted.');
    }

    protected function validateMedia(Request $request, ?MediaAsset $media = null): array
    {
        $assetType = $media?->asset_type ?: (string) $request->input('asset_type');

        $validator = Validator::make($request->all(), [
            'asset_type' => [$media ? 'nullable' : 'required', 'in:image,video'],
            'media_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,avif,svg', 'max:5120'],
            'external_url' => ['nullable', 'url'],
            'caption' => ['nullable', 'string'],
            'credit' => ['nullable', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($request, $assetType, $media): void {
            if ($assetType === 'image' && ! $request->hasFile('media_file') && ! $media?->file_path) {
                $validator->errors()->add('media_file', 'An image file is required for image media.');
            }

            if ($assetType === 'video') {
                $url = trim((string) $request->input('external_url'));

                if ($url === '') {
                    $validator->errors()->add('external_url', 'A YouTube URL is required for video media.');

                    return;
                }

                if (! MediaAsset::parseYouTubeId($url)) {
                    $validator->errors()->add('external_url', 'Video media currently supports YouTube URLs only.');
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
            $payload['storage_type'] = 'upload';
            $payload['external_url'] = null;
            $payload['file_path'] = $request->hasFile('media_file')
                ? $request->file('media_file')->store('lotg-media/images', 'public')
                : $media?->file_path;
        }

        if ($assetType === 'video') {
            $payload['storage_type'] = 'youtube';
            $payload['file_path'] = null;
            $payload['external_url'] = trim((string) ($validated['external_url'] ?? ''));
        }

        return $payload;
    }

    protected function deleteStoredFileIfNeeded(?string $filePath): void
    {
        if (! $filePath || str_starts_with($filePath, 'demo/')) {
            return;
        }

        if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
            return;
        }

        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
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
}
