<?php

namespace App\Jobs;

use App\Models\TalosMedia;
use App\Services\StorageSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Intervention\Image\Laravel\Facades\Image;

class ConvertImageToWebp implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(public int $mediaId) {}

    public function handle(StorageSettings $storage): void
    {
        $media = TalosMedia::find($this->mediaId);

        // 'failed' is included so a previously-failed conversion can be re-dispatched
        // (e.g. via `php artisan talos:reconvert-media`) without a status reset first.
        if (! $media || ! in_array($media->status, ['converting', 'failed'], true)) {
            return;
        }

        $disk         = $storage->mediaDisk();
        $originalPath = $media->path;

        if (! $disk->exists($originalPath)) {
            // File genuinely missing — not a success, leave it distinguishable from 'ready'.
            $media->update(['status' => 'failed']);
            return;
        }

        $contents = $disk->get($originalPath);
        $image    = Image::read($contents);
        $width    = $image->width();
        $height   = $image->height();
        $encoded  = (string) $image->toWebp(85);

        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $originalPath);
        $disk->put($webpPath, $encoded);

        if ($webpPath !== $originalPath) {
            $disk->delete($originalPath);
        }

        $media->update([
            'path'      => $webpPath,
            'ext'       => 'webp',
            'mime_type' => 'image/webp',
            'size'      => $disk->size($webpPath),
            'width'     => $width,
            'height'    => $height,
            'status'    => 'ready',
        ]);
    }

    public function failed(\Throwable $e): void
    {
        \Log::error("ConvertImageToWebp failed for media #{$this->mediaId}: {$e->getMessage()}");
        TalosMedia::where('id', $this->mediaId)->update(['status' => 'failed']);
    }
}
