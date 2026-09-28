<?php

namespace App\Console\Commands;

use App\Jobs\ConvertImageToWebp;
use App\Models\TalosMedia;
use Illuminate\Console\Command;

class ReconvertMediaCommand extends Command
{
    protected $signature = 'talos:reconvert-media
                            {--dry-run : List affected records without dispatching anything}';

    protected $description = 'Re-dispatch WebP conversion for images stuck without a width/height (e.g. after a prior conversion failure)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $stuck = TalosMedia::whereNull('width')
            ->where('mime_type', 'like', 'image/%')
            ->where('mime_type', '!=', 'image/svg+xml')
            ->get(['id', 'name', 'path', 'status']);

        if ($stuck->isEmpty()) {
            $this->info('No stuck image records found.');

            return self::SUCCESS;
        }

        foreach ($stuck as $media) {
            $this->line(sprintf('  #%d %s (%s) — was: %s', $media->id, $media->name, $media->path, $media->status));

            if (! $dryRun) {
                $media->update(['status' => 'converting']);
                ConvertImageToWebp::dispatch($media->id);
            }
        }

        $this->info($dryRun
            ? "{$stuck->count()} records would be re-queued for conversion. Re-run without --dry-run to apply."
            : "{$stuck->count()} records re-queued for conversion.");

        return self::SUCCESS;
    }
}
