<?php

namespace App\Services\Api;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ObjectStorageService
{
    public function diskName(): string
    {
        return (string) config('filesystems.uploads', 'r2');
    }

    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    /**
     * Store an uploaded file under {folder}/{YYYY}/{MM}/{uuid}.{ext}.
     *
     * @return array{path: string, url: string}
     */
    public function storeUploadedFile(UploadedFile $file, string $folder): array
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $filename = Str::uuid()->toString().'.'.$extension;
        $directory = trim($folder, '/').'/'.now()->format('Y/m');
        $path = $directory.'/'.$filename;

        $this->disk()->putFileAs($directory, $file, $filename, [
            'visibility' => 'public',
        ]);

        return [
            'path' => $path,
            'url' => $this->disk()->url($path),
        ];
    }

    /**
     * Store a period-scoped file under {prefix}/{periodId}/{uuid}.{ext}.
     *
     * @return array{path: string, url: string}
     */
    public function storeForPeriod(UploadedFile $file, string $periodId, string $prefix = 'rhpp'): array
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $filename = Str::uuid()->toString().'.'.$extension;
        $directory = trim($prefix, '/').'/'.$periodId;
        $path = $directory.'/'.$filename;

        $this->disk()->putFileAs($directory, $file, $filename, [
            'visibility' => 'public',
        ]);

        return [
            'path' => $path,
            'url' => $this->disk()->url($path),
        ];
    }

    public function delete(string $path): bool
    {
        if ($path === '' || ! $this->disk()->exists($path)) {
            return false;
        }

        return $this->disk()->delete($path);
    }

    public function exists(string $path): bool
    {
        return $path !== '' && $this->disk()->exists($path);
    }
}
