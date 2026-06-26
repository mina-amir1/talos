<?php

namespace App\Services;

use App\Models\TalosMedia;
use Illuminate\Support\Collection;

class EntryTransformerService
{
    private const SYSTEM_KEYS = [
        'id', 'slug', 'locale', 'localizations_id',
        'created_by', 'updated_by', 'published_at', 'created_at', 'updated_at',
    ];

    public function __construct(
        private ContentTypeService  $typeService,
        private DynamicModelService $modelService,
        private ComponentService    $componentService,
    ) {}

    public function transform(array $entries, array $attributes, ?array $apiFields, string $locale): array
    {
        $entries = $this->resolveMediaFields($entries, $attributes);
        $entries = $this->resolveRelationFields($entries, $attributes, $locale);
        $entries = array_map(fn($entry) => $this->applyApiFields($entry, $apiFields), $entries);

        return $entries;
    }

    public function filterToSchema(array $entries, array $attributes): array
    {
        $publicAttributes = array_keys(array_filter($attributes, fn($def) => ! ($def['private'] ?? false)));
        $allowed          = array_flip(array_merge(self::SYSTEM_KEYS, $publicAttributes));

        return array_map(fn($entry) => array_intersect_key($entry, $allowed), $entries);
    }

    public function applyApiFields(array $entry, ?array $apiFields): array
    {
        if ($apiFields === null) {
            return $entry;
        }

        $keep = array_flip(array_merge(self::SYSTEM_KEYS, $apiFields));

        return array_intersect_key($entry, $keep);
    }

    private function resolveMediaFields(array $entries, array $attributes): array
    {
        if (empty($entries)) {
            return $entries;
        }

        $entries = $this->filterToSchema($entries, $attributes);

        $allIds = [];
        foreach ($entries as $entry) {
            $this->collectMediaIds($entry, $attributes, $allIds);
        }

        if (empty($allIds)) {
            return $entries;
        }

        $mediaMap = TalosMedia::whereIn('id', array_unique($allIds))->get()->keyBy('id');

        foreach ($entries as &$entry) {
            $entry = $this->resolveEntryMedia($entry, $attributes, $mediaMap);
        }
        unset($entry);

        return $entries;
    }

    private function collectMediaIds(array $entry, array $attributes, array &$ids): void
    {
        foreach ($attributes as $field => $def) {
            $type = $def['type'] ?? '';
            $val  = $entry[$field] ?? null;

            if ($type === 'media') {
                if (is_array($val)) {
                    foreach ($val as $id) {
                        if (is_numeric($id) && $id) $ids[] = (int) $id;
                    }
                } elseif (is_numeric($val) && $val) {
                    $ids[] = (int) $val;
                }
            } elseif ($type === 'component' && is_array($val)) {
                $compUid  = $def['component'] ?? ($def['components'][0] ?? null);
                $subAttrs = $compUid ? ($this->componentService->find($compUid)['attributes'] ?? []) : [];
                if ($def['repeatable'] ?? false) {
                    foreach ($val as $row) {
                        if (is_array($row)) $this->collectMediaIds($row, $subAttrs, $ids);
                    }
                } else {
                    $this->collectMediaIds($val, $subAttrs, $ids);
                }
            } elseif ($type === 'repeater' && is_array($val)) {
                $subAttrs = $def['subFields'] ?? [];
                foreach ($val as $row) {
                    if (is_array($row)) $this->collectMediaIds($row, $subAttrs, $ids);
                }
            }
        }
    }

    private function resolveEntryMedia(array $entry, array $attributes, Collection $mediaMap): array
    {
        foreach ($attributes as $field => $def) {
            $type = $def['type'] ?? '';
            $val  = $entry[$field] ?? null;

            if ($type === 'media') {
                $isMultiple = $def['multiple'] ?? false;
                if ($isMultiple) {
                    $ids           = is_array($val) ? $val : [];
                    $entry[$field] = array_values(array_filter(
                        array_map(fn($id) => $mediaMap->get((int) $id)?->toArray(), $ids)
                    ));
                } else {
                    $id            = is_numeric($val) ? (int) $val : null;
                    $entry[$field] = $id ? $mediaMap->get($id)?->toArray() : null;
                }
            } elseif ($type === 'component' && is_array($val)) {
                $compUid  = $def['component'] ?? ($def['components'][0] ?? null);
                $subAttrs = $compUid ? ($this->componentService->find($compUid)['attributes'] ?? []) : [];
                if ($def['repeatable'] ?? false) {
                    $entry[$field] = array_map(
                        fn($row) => is_array($row) ? $this->resolveEntryMedia($row, $subAttrs, $mediaMap) : $row,
                        $val
                    );
                } else {
                    $entry[$field] = $this->resolveEntryMedia($val, $subAttrs, $mediaMap);
                }
            } elseif ($type === 'repeater' && is_array($val)) {
                $subAttrs      = $def['subFields'] ?? [];
                $entry[$field] = array_map(
                    fn($row) => is_array($row) ? $this->resolveEntryMedia($row, $subAttrs, $mediaMap) : $row,
                    $val
                );
            }
        }

        return $entry;
    }

    private function resolveRelationFields(array $entries, array $attributes, string $locale): array
    {
        if (empty($entries)) {
            return $entries;
        }

        $relationFields = array_filter($attributes, fn($f) => ($f['type'] ?? '') === 'relation' && ! empty($f['target']));

        if (empty($relationFields)) {
            return $entries;
        }

        $defaultLocale = config('talos.default_locale');

        $idsByTarget = [];
        foreach ($relationFields as $field => $def) {
            $isMultiple = in_array($def['relation'] ?? 'manyToOne', ['oneToMany', 'manyToMany']);
            foreach ($entries as $entry) {
                $val = $entry[$field] ?? null;
                if ($isMultiple) {
                    foreach ((array) $val as $id) {
                        if (is_numeric($id) && $id) $idsByTarget[$def['target']][] = (int) $id;
                    }
                } elseif (is_numeric($val) && $val) {
                    $idsByTarget[$def['target']][] = (int) $val;
                }
            }
        }

        $mapsByTarget = [];
        foreach ($idsByTarget as $targetUid => $ids) {
            $targetType = $this->typeService->find($targetUid);
            if (! $targetType) continue;

            $targetModel = $this->modelService->make($targetUid);
            $targetAttrs = $targetType['attributes'] ?? [];
            $targetI18n  = (bool) ($targetType['options']['i18n'] ?? false);
            $records     = $targetModel->newQuery()->whereIn('id', array_unique($ids))->get();

            if ($targetI18n && $locale !== $defaultLocale) {
                $localizationsIds = $records->pluck('localizations_id')->filter()->unique()->values()->all();

                if (! empty($localizationsIds)) {
                    $localeVersions = $targetModel->newQuery()
                        ->whereIn('localizations_id', $localizationsIds)
                        ->where('locale', $locale)
                        ->get()
                        ->keyBy('localizations_id');

                    $records = $records->map(fn($r) => $localeVersions->get($r->localizations_id) ?? $r);
                }
            }

            $mapsByTarget[$targetUid] = $records->keyBy('id')->map(
                fn($r) => $this->filterToSchema([$r->toArray()], $targetAttrs)[0]
            );
        }

        foreach ($entries as &$entry) {
            foreach ($relationFields as $field => $def) {
                $isMultiple    = in_array($def['relation'] ?? 'manyToOne', ['oneToMany', 'manyToMany']);
                $map           = $mapsByTarget[$def['target']] ?? collect();
                $val           = $entry[$field] ?? null;

                if ($isMultiple) {
                    $entry[$field] = array_values(array_filter(
                        array_map(fn($id) => $map->get((int) $id), (array) $val)
                    ));
                } else {
                    $id            = is_numeric($val) ? (int) $val : null;
                    $entry[$field] = $id ? $map->get($id) : null;
                }
            }
        }
        unset($entry);

        return $entries;
    }
}
