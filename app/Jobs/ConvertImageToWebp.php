<?php

namespace App\Jobs;

use App\Models\TalosMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ConvertImageToWebp implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(public int $mediaId, public string $disk) {}

    public function handle(): void
    {
        $media = TalosMedia::find($this->mediaId);

        if (! $media || $media->status !== 'converting') {
            return;
        }

        $originalPath = $media->path;

        if (! Storage::disk($this->disk)->exists($originalPath)) {
            $media->update(['status' => 'ready']);
            return;
        }

        $contents = Storage::disk($this->disk)->get($originalPath);
        $image    = Image::read($contents);
        $width    = $image->width();
        $height   = $image->height();
        $encoded  = (string) $image->toWebp(85);

        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $originalPath);
        Storage::disk($this->disk)->put($webpPath, $encoded);

        // Remove original only if the path changed (e.g. was .jpg, now .webp)
        if ($webpPath !== $originalPath) {
            Storage::disk($this->disk)->delete($originalPath);
        }

        $media->update([
            'path'      => $webpPath,
            'ext'       => 'webp',
            'mime_type' => 'image/webp',
            'size'      => Storage::disk($this->disk)->size($webpPath),
            'width'     => $width,
            'height'    => $height,
            'status'    => 'ready',
        ]);
    }

    public function failed(\Throwable $e): void
    {
        TalosMedia::where('id', $this->mediaId)->update(['status' => 'ready']);
    }
}
