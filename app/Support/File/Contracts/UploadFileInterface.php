<?php

namespace App\Support\File\Contracts;

use App\Models\File;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Http\UploadedFile;

interface UploadFileInterface
{
    /**
     * @return array{disk: string, path: string, url: string, original_name: string, mime_type: ?string, size: int}
     */
    public function uploadOnly(
        UploadedFile $file,
        string $directory = 'files',
        string $disk = 'public',
    ): array;

    public function createFromPath(
        EloquentModel $fileable,
        string $pathOrUrl,
        string $category,
        ?string $tenantId = null,
        string $disk = 'public',
    ): File;

    public function delete(File $file): void;

    /**
     * @param  iterable<int, File>  $files
     */
    public function deleteMany(iterable $files): void;

    public function normalizePath(string $pathOrUrl, string $disk = 'public'): string;
}
