<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FileRejectedException;
use App\Http\Controllers\Controller;
use App\Models\TalosFile;
use App\Services\FileUploadService;
use App\Services\StorageSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileUploadController extends Controller
{
    public function __construct(
        private FileUploadService $uploads,
        private StorageSettings   $storage,
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
        ]);

        try {
            $file = $this->uploads->store($request->file('file'));
        } catch (FileRejectedException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'id'            => $file->id,
            'original_name' => $file->original_name,
            'ext'           => $file->ext,
            'size'          => $file->size,
            'mime_type'     => $file->mime_type,
        ], 201);
    }

    public function download(int $id): StreamedResponse
    {
        $file = TalosFile::findOrFail($id);

        $disk = $this->storage->mediaDisk();

        abort_unless($disk->exists($file->path), 404);

        return response()->streamDownload(
            fn () => print($disk->get($file->path)),
            $file->original_name,
            ['Content-Type' => $file->mime_type],
        );
    }
}
