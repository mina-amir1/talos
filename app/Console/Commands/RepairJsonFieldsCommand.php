<?php

namespace App\Console\Commands;

use App\Services\ContentTypeService;
use App\Services\DynamicModelService;
use Illuminate\Console\Command;

class RepairJsonFieldsCommand extends Command
{
    protected $signature = 'talos:repair-json
                            {--dry-run : List affected entries without writing}
                            {--type= : Limit the scan to one content type UID, e.g. api.libido_desire_page}';

    protected $description = 'Repair entries whose JSON fields were stored double-encoded and read back as strings';

    public function handle(ContentTypeService $types, DynamicModelService $models): int
    {
        $dryRun   = (bool) $this->option('dry-run');
        $only     = $this->option('type');
        $repaired = 0;

        foreach ($types->all() as $schema) {
            $uid = $schema['__uid'];

            if ($only && $only !== $uid) {
                continue;
            }

            $jsonFields = array_keys(array_filter(
                $schema['attributes'] ?? [],
                fn($field) => ContentTypeService::isJsonStored($field)
            ));

            if (empty($jsonFields)) {
                continue;
            }

            $models->make($uid)->newQuery()->each(function ($entry) use ($jsonFields, $uid, $dryRun, &$repaired) {
                $fixed = [];

                foreach ($jsonFields as $field) {
                    $decoded = $this->decodeStoredString($entry->$field);

                    if ($decoded === null) {
                        continue;
                    }

                    $entry->$field = $decoded;
                    $fixed[]       = $field;
                }

                if (empty($fixed)) {
                    return;
                }

                $repaired++;
                $this->line(sprintf('  %s #%s — %s', $uid, $entry->getKey(), implode(', ', $fixed)));

                if (! $dryRun) {
                    // A repair restores what was meant to be stored; it is not a content edit.
                    $entry->timestamps = false;
                    $entry->save();
                }
            });
        }

        if ($repaired === 0) {
            $this->info('No double-encoded JSON fields found.');

            return self::SUCCESS;
        }

        $this->info($dryRun
            ? "{$repaired} entries would be repaired. Re-run without --dry-run to apply."
            : "{$repaired} entries repaired.");

        return self::SUCCESS;
    }

    /**
     * A correctly stored JSON field casts to an array. One that reads back as a string was
     * encoded twice, so decoding it once yields the intended structure. Anything that does
     * not decode to an array is left alone.
     */
    private function decodeStoredString(mixed $value): ?array
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
