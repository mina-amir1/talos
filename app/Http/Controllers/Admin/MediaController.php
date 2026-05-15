<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalosMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = TalosMedia::latest();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            if ($request->type === 'image') {
                $query->where('mime_type', 'like', 'image/%');
            } else {
                $query->where('mime_type', 'not like', 'image/%');
            }
        }

        $media = $query->paginate(30)->withQueryString();

        return view('talos.media.index', compact('media'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file'   => 'required|file|max:51200', // 50MB
            'folder' => 'nullable|string|max:255',
        ]);

        $file   = $request->file('file');
        $disk   = config('talos.media_disk', 'public');
        $dir    = config('talos.media_directory', 'talos/media');
        $folder = $request->input('folder', '');
        $path   = trim($dir . '/' . $folder, '/');

        $hash     = md5_file($file->getRealPath());
        $ext      = strtolower($file->getClientOriginalExtension());
        $filename = $hash . '.' . $ext;

        $existing = TalosMedia::where('hash', $hash)->first();

        if ($existing) {
            return response()->json(['data' => $existing]);
        }

        $storedPath = $file->storeAs($path, $filename, $disk);

        $width  = null;
        $height = null;

        if (str_starts_with($file->getMimeType(), 'image/')) {
            try {
                $image  = Image::read($file->getRealPath());
                $width  = $image->width();
                $height = $image->height();
            } catch (\Exception) {
            }
        }

        $media = TalosMedia::create([
            'name'          => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'mime_type'     => $file->getMimeType(),
            'ext'           => $ext,
            'size'          => $file->getSize(),
            'url'           => $storedPath,   // store path; URL is derived via getUrlAttribute()
            'path'          => $storedPath,
            'width'         => $width,
            'height'        => $height,
            'hash'          => $hash,
            'folder'        => $folder ?: null,
            'uploaded_by'   => session('talos_user_id'),
        ]);

        return response()->json(['data' => $media]);
    }

    public function destroy(int $id)
    {
        $media = TalosMedia::findOrFail($id);
        $disk  = config('talos.media_disk', 'public');

        Storage::disk($disk)->delete($media->path);
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
