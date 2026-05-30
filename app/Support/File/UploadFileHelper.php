<?php

namespace App\Support\File;

use App\Models\File;
use App\Support\File\Contracts\UploadFileInterface;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class UploadFileHelper implements UploadFileInterface
{
    /**
     * @return array{disk: string, path: string, url: string, original_name: string, mime_type: ?string, size: int}
     */
    public function uploadOnly(
        UploadedFile $file,
        string $directory = 'files',
        string $disk = 'public',
    ): array {
        $path = $file->store($directory, $disk);

        if ($path === false) {
            throw new RuntimeException('Unable to store uploaded file.');
        }

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($disk);

        return [
            'disk' => $disk,
            'path' => $path,
            'url' => $storage->url($path),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => (int) $file->getSize(),
        ];
    }

    public function createFromPath(
        EloquentModel $fileable,
        string $pathOrUrl,
        string $category,
        ?string $tenantId = null,
        string $disk = 'public',
    ): File {
        return File::query()->create([
            'fileable_id' => $fileable->getKey(),
            'fileable_type' => $fileable::class,
            'category' => $category,
            'tenant_id' => $tenantId,
            'disk' => $disk,
            'path' => $this->normalizePath($pathOrUrl, $disk),
        ]);
    }

    public function delete(File $file): void
    {
        /** @var EloquentModel $file */
        $file->delete();
    }

    /**
     * @param  iterable<int, File>  $files
     */
    public function deleteMany(iterable $files): void
    {
        foreach ($files as $file) {
            $this->delete($file);
        }
    }

    public function normalizePath(string $pathOrUrl, string $disk = 'public'): string
    {
        $path = $pathOrUrl;

        if (filter_var($pathOrUrl, FILTER_VALIDATE_URL)) {
            $path = parse_url($pathOrUrl, PHP_URL_PATH) ?? $pathOrUrl;
        }

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($disk);
        $baseUrl = $storage->url('');
        $path = Str::startsWith($path, $baseUrl)
            ? Str::after($path, $baseUrl)
            : $path;

        $path = Str::startsWith($path, '/storage/')
            ? Str::after($path, '/storage/')
            : $path;

        return ltrim($path, '/');
    }
}
