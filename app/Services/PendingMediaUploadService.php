<?php

namespace App\Services;

use App\Models\PendingMediaUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PendingMediaUploadService
{
    public function detectUploadedFileType(UploadedFile $file): ?string
    {
        $mimeType = strtolower((string) $file->getMimeType());
        $originalName = strtolower((string) $file->getClientOriginalName());

        if (str_starts_with($mimeType, 'image/') || preg_match('/\.(jpg|jpeg|png|gif|webp|avif|svg)$/i', $originalName)) {
            return 'image';
        }

        if ($mimeType === 'video/mp4' || preg_match('/\.mp4$/i', $originalName)) {
            return 'video';
        }

        return null;
    }

    public function create(UploadedFile $file, int $userId, string $storageDisk): PendingMediaUpload
    {
        $assetType = $this->detectUploadedFileType($file);

        if (! in_array($assetType, ['image', 'video'], true)) {
            throw new RuntimeException('Unsupported upload type.');
        }

        $uuid = (string) Str::uuid();
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: ($assetType === 'image' ? 'bin' : 'mp4');
        $tempPath = $file->storeAs('lotg-media/pending', $uuid.'.'.$extension, $storageDisk);

        return PendingMediaUpload::create([
            'uuid' => $uuid,
            'user_id' => $userId,
            'asset_type' => $assetType,
            'storage_disk' => $storageDisk,
            'temp_path' => $tempPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => (int) $file->getSize(),
        ]);
    }

    public function promote(PendingMediaUpload $upload, string $targetDisk): string
    {
        $extension = pathinfo($upload->temp_path, PATHINFO_EXTENSION);
        $filename = Str::random(40).($extension !== '' ? '.'.$extension : '');
        $targetDirectory = $upload->asset_type === 'image' ? 'lotg-media/images' : 'lotg-media/videos';
        $targetPath = $targetDirectory.'/'.$filename;

        if ($upload->storage_disk === $targetDisk) {
            Storage::disk($targetDisk)->move($upload->temp_path, $targetPath);
        } else {
            $stream = Storage::disk($upload->storage_disk)->readStream($upload->temp_path);

            if (! is_resource($stream)) {
                throw new RuntimeException('Unable to read the pending upload stream.');
            }

            try {
                Storage::disk($targetDisk)->writeStream($targetPath, $stream);
            } finally {
                fclose($stream);
            }

            Storage::disk($upload->storage_disk)->delete($upload->temp_path);
        }

        $upload->delete();

        return $targetPath;
    }

    public function delete(PendingMediaUpload $upload): void
    {
        Storage::disk($upload->storage_disk)->delete($upload->temp_path);
        $upload->delete();
    }
}
