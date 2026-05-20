<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ConvertImageToWebp;
use App\Models\TalosMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    private function disk(): string
    {
        return config('talos.media_disk', 'public');
    }

    private function baseDir(): string
    {
        return config('talos.media_directory', 'talos/media');
    }

    // Resolve the storage path for a given folder (relative path like "blog/images")
    private function storageDir(string $folder = ''): string
    {
        return $folder ? $this->baseDir() . '/' . $folder : $this->baseDir();
    }

    public function index(Request $request)
    {
        $folder  = trim($request->input('path', ''), '/');
        $disk    = $this->disk();
        $dir     = $this->storageDir($folder);

        // Immediate subdirectories
        $rawDirs = Storage::disk($disk)->directories($dir);
        $folders = array_map(fn($d) => [
            'name'  => basename($d),
            'path'  => $folder ? $folder . '/' . basename($d) : basename($d),
            'count' => count(Storage::disk($disk)->files($d)),
        ], $rawDirs);

        // All directories (for sidebar tree)
        $allDirs = array_map(
            fn($d) => trim(Str::after($d, $this->baseDir() . '/'), '/'),
            Storage::disk($disk)->allDirectories($this->baseDir())
        );

        // Files in this folder from DB (no folder filter = show all assets)
        $query = TalosMedia::when($folder !== '', fn($q) => $q->where('folder', $folder))->latest();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            $query->when(
                $request->type === 'image',
                fn($q) => $q->where('mime_type', 'like', 'image/%'),
                fn($q) => $q->where('mime_type', 'not like', 'image/%'),
            );
        }

        $media = $query->paginate(30)->withQueryString();

        return view('talos.media.index', compact('media', 'folders', 'allDirs', 'folder'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file'   => 'required|file|max:51200',
            'folder' => 'nullable|string|max:255',
        ]);

        $file    = $request->file('file');
        $disk    = $this->disk();
        $folder  = trim($request->input('folder', ''), '/');
        $dir     = $this->storageDir($folder);
        $hash    = md5_file($file->getRealPath());

        $existing = TalosMedia::where('hash', $hash)->first();
        if ($existing) {
            return response()->json(['data' => $existing]);
        }

        $mimeType      = $file->getMimeType();
        $isImage       = str_starts_with($mimeType, 'image/');
        // SVGs are vector — store as-is, no conversion
        $willConvert   = $isImage && $mimeType !== 'image/svg+xml';

        $ext        = strtolower($file->getClientOriginalExtension());
        $filename   = $hash . '.' . $ext;
        $storedPath = $file->storeAs($dir, $filename, $disk);
        $size       = $file->getSize();

        $media = TalosMedia::create([
            'name'        => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'mime_type'   => $mimeType,
            'ext'         => $ext,
            'size'        => $size,
            'url'         => $storedPath,
            'path'        => $storedPath,
            'width'       => null,
            'height'      => null,
            'hash'        => $hash,
            'folder'      => $folder ?: null,
            'uploaded_by' => session('talos_user_id'),
            'status'      => $willConvert ? 'converting' : 'ready',
        ]);

        if ($willConvert) {
            ConvertImageToWebp::dispatch($media->id, $disk);
        }

        return response()->json(['data' => $media]);
    }

    public function storeFolder(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:128',
            'parent' => 'nullable|string|max:255',
        ]);

        $parent  = trim($request->input('parent', ''), '/');
        $name    = Str::slug($request->input('name'), '-');
        $newPath = $parent ? $parent . '/' . $name : $name;

        Storage::disk($this->disk())->makeDirectory($this->storageDir($newPath));

        return redirect()
            ->route('talos.media.index', array_filter(['path' => $parent]))
            ->with('success', 'Folder "' . $name . '" created.');
    }

    public function destroyFolder(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        $folder = trim($request->input('path'), '/');
        $parent = str_contains($folder, '/') ? dirname($folder) : '';
        $disk   = $this->disk();

        // Delete all files in this folder and any subfolders from storage + DB
        $affected = TalosMedia::where('folder', $folder)
            ->orWhere('folder', 'like', $folder . '/%')
            ->get();

        foreach ($affected as $media) {
            Storage::disk($disk)->delete($media->path);
            $media->delete();
        }

        Storage::disk($disk)->deleteDirectory($this->storageDir($folder));

        return redirect()
            ->route('talos.media.index', array_filter(['path' => $parent]))
            ->with('success', 'Folder and its contents deleted.');
    }

    public function moveFile(Request $request, int $id)
    {
        $request->validate(['folder' => 'nullable|string|max:255']);

        $media     = TalosMedia::findOrFail($id);
        $disk      = $this->disk();
        $newFolder = trim($request->input('folder', ''), '/');
        $filename  = basename($media->path);
        $newPath   = $this->storageDir($newFolder) . '/' . $filename;

        Storage::disk($disk)->move($media->path, $newPath);

        $media->update([
            'path'   => $newPath,
            'url'    => $newPath,
            'folder' => $newFolder ?: null,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(int $id)
    {
        $media = TalosMedia::findOrFail($id);

        Storage::disk($this->disk())->delete($media->path);
        $media->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'File deleted.');
    }

    public function show(int $id)
    {
        return response()->json(['data' => TalosMedia::findOrFail($id)]);
    }
}
