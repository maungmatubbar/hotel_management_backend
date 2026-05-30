<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Support\File\Contracts\UploadFileInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class FileUploadController extends Controller
{
    public function store(Request $request, UploadFileInterface $uploadFile): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['nullable', 'required_without:files', 'image', 'max:5120'],
            'files' => ['nullable', 'required_without:file', 'array', 'max:10'],
            'files.*' => ['required', 'image', 'max:5120'],
            'directory' => ['nullable', 'string', 'max:100'],
        ]);

        $directory = $validated['directory'] ?? 'uploads';
        $files = collect($request->file('files') ?? [])
            ->when($request->file('file') instanceof UploadedFile, fn ($files) => $files->prepend($request->file('file')));

        return $this->successResponse(
            data: $files
                ->filter(fn (mixed $file): bool => $file instanceof UploadedFile)
                ->map(fn (UploadedFile $file): array => $uploadFile->uploadOnly($file, $directory))
                ->values()
                ->all(),
        );
    }
}
