<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalosFile;
use App\Services\StorageSettings;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(private StorageSettings $storage) {}

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
