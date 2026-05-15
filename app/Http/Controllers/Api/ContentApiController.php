<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TalosMedia;
use App\Services\ComponentService;
use App\Services\ContentTypeService;
use App\Services\DynamicModelService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class ContentApiController extends Controller
{
    public function __construct(
        private ContentTypeService $typeService,
        private DynamicModelService $modelService,
        private ComponentService $componentService,
    ) {}

    public function index(Request $request, string $name): JsonResponse
    {
        $contentType = $this->resolveType($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        $uid   = $contentType['__uid'];
        $model = $this->modelService->make($uid);

        // Single types return one entry instead of a paginated list
        if (($contentType['kind'] ?? 'collectionType') === 'singleType') {
            $query = $model->newQuery();

            if ($contentType['options']['draftAndPublish'] ?? false) {
                $query->whereNotNull('published_at');
            }

            $entry = $query->first();

            if (! $entry) {
                return response()->json(['data' => null]);
            }

            $data = $this->processEntries(
                [$entry->toArray()],
                $contentType['attributes'] ?? []
            )[0];

            return response()->json(['data' => $data]);
        }

        $query = $model->newQuery();

        if ($contentType['options']['draftAndPublish'] ?? false) {
            $query->whereNotNull('published_at');
        }

        if ($request->has('filters')) {
            foreach ($request->filters as $field => $value) {
                $query->where($field, $value);
            }
        }

        if ($request->filled('sort')) {
            [$field, $dir] = array_pad(explode(':', $request->sort), 2, 'asc');
            $query->orderBy($field, $dir);
        } else {
            $query->latest();
        }

        $pageSize = min((int) ($request->pagination['pageSize'] ?? config('talos.default_page_size')), 100);
        $page     = (int) ($request->pagination['page'] ?? 1);

        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);
        $items     = $this->processEntries(
            collect($paginator->items())->map->toArray()->all(),
            $contentType['attributes'] ?? []
        );

        return response()->json([
            'data' => $items,
            'meta' => [
                'pagination' => [
                    'page'      => $paginator->currentPage(),
                    'pageSize'  => $paginator->perPage(),
                    'pageCount' => $paginator->lastPage(),
                    'total'     => $paginator->total(),
                ],
            ],
        ]);
    }

    public function show(string $name, int $id): JsonResponse
    {
        $contentType = $this->resolveType($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        $uid   = $contentType['__uid'];
        $model = $this->modelService->make($uid);
        $entry = $model->newQuery()->findOrFail($id);

        $data = $this->processEntries(
            [$entry->toArray()],
            $contentType['attributes'] ?? []
        )[0];

        return response()->json(['data' => $data]);
    }

    public function store(Request $request, string $name): JsonResponse
    {
        $contentType = $this->resolveType($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        $uid   = $contentType['__uid'];
        $rules = $this->buildValidationRules($contentType['attributes'] ?? []);

        $validated = $request->validate($rules);

        $model = $this->modelService->make($uid);

        // Single types: upsert the one row
        if (($contentType['kind'] ?? 'collectionType') === 'singleType') {
            $entry = $model->newQuery()->first();

            if ($entry) {
                $entry->update($validated);
            } else {
                $entry = $model->newQuery()->create($validated);
            }

            return response()->json(['data' => $entry]);
        }

        $entry = $model->newQuery()->create($validated);

        return response()->json(['data' => $entry], 201);
    }

    public function update(Request $request, string $name, int $id): JsonResponse
    {
        $contentType = $this->resolveType($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        $uid   = $contentType['__uid'];
        $model = $this->modelService->make($uid);
        $entry = $model->newQuery()->findOrFail($id);
        $entry->update($request->all());

        return response()->json(['data' => $entry]);
    }

    public function destroy(string $name, int $id): JsonResponse
    {
        $contentType = $this->resolveType($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        $uid = $contentType['__uid'];
        $this->modelService->make($uid)->newQuery()->findOrFail($id)->delete();

        return response()->json(['data' => null], 200);
    }

    public function updateSingle(Request $request, string $name): JsonResponse
    {
        $contentType = $this->resolveType($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        if (($contentType['kind'] ?? 'collectionType') !== 'singleType') {
            return response()->json(['error' => "Use PUT /{$name}/{id} for collection types."], 400);
        }

        $uid   = $contentType['__uid'];
        $model = $this->modelService->make($uid);
        $entry = $model->newQuery()->first();

        if (! $entry) {
            $rules     = $this->buildValidationRules($contentType['attributes'] ?? []);
            $validated = $request->validate($rules);
            $entry     = $model->newQuery()->create($validated);
        } else {
            $entry->update($request->all());
        }

        return response()->json(['data' => $entry]);
    }

    public function destroySingle(string $name): JsonResponse
    {
        $contentType = $this->resolveType($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        if (($contentType['kind'] ?? 'collectionType') !== 'singleType') {
            return response()->json(['error' => "Use DELETE /{$name}/{id} for collection types."], 400);
        }

        $uid = $contentType['__uid'];
        $this->modelService->make($uid)->newQuery()->delete();

        return response()->json(['data' => null], 200);
    }

    private function processEntries(array $entries, array $attributes): array
    {
        $entries = $this->resolveMediaFields($entries, $attributes);
        $entries = $this->resolveRelationFields($entries, $attributes);
        return $entries;
    }

    private function resolveRelationFields(array $entries, array $attributes): array
    {
        if (empty($entries)) {
            return $entries;
        }

        $relationFields = array_filter($attributes, fn($f) => ($f['type'] ?? '') === 'relation' && !empty($f['target']));

        if (empty($relationFields)) {
            return $entries;
        }

        // Collect all referenced IDs per target UID in one pass
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

        // Batch fetch each target type
        $mapsByTarget = [];
        foreach ($idsByTarget as $targetUid => $ids) {
            $targetType = $this->typeService->find($targetUid);
            if (! $targetType) continue;

            $records = $this->modelService->make($targetUid)
                ->newQuery()
                ->whereIn('id', array_unique($ids))
                ->get();

            // Filter related entry data to only schema-defined fields
            $targetAttrs = $targetType['attributes'] ?? [];
            $mapsByTarget[$targetUid] = $records->keyBy('id')->map(function ($r) use ($targetAttrs) {
                return $this->filterToSchema([$r->toArray()], $targetAttrs)[0];
            });
        }

        // Replace IDs with resolved data
        foreach ($entries as &$entry) {
            foreach ($relationFields as $field => $def) {
                $isMultiple = in_array($def['relation'] ?? 'manyToOne', ['oneToMany', 'manyToMany']);
                $map        = $mapsByTarget[$def['target']] ?? collect();
                $val        = $entry[$field] ?? null;

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

    private function filterToSchema(array $entries, array $attributes): array
    {
        static $systemKeys = ['id', 'created_by', 'updated_by', 'published_at', 'created_at', 'updated_at'];

        $allowed = array_flip(array_merge($systemKeys, array_keys($attributes)));

        return array_map(fn($entry) => array_intersect_key($entry, $allowed), $entries);
    }

    private function resolveMediaFields(array $entries, array $attributes): array
    {
        if (empty($entries)) {
            return $entries;
        }

        // Strip DB columns not present in the current schema
        $entries = $this->filterToSchema($entries, $attributes);

        // Single pass: collect every media ID referenced at any depth
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

    private function resolveType(string $name): ?array
    {
        foreach ($this->typeService->all() as $type) {
            $isSingle     = ($type['kind'] ?? 'collectionType') === 'singleType';
            $nameToMatch  = $isSingle
                ? ($type['info']['singularName'] ?? '')
                : ($type['info']['pluralName'] ?? '');

            if ($nameToMatch === $name) {
                return $type;
            }
        }

        return null;
    }

    private function buildValidationRules(array $attributes): array
    {
        $rules = [];

        foreach ($attributes as $name => $field) {
            $rule = [];

            if ($field['required'] ?? false) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }

            $rule[] = match ($field['type']) {
                'string', 'text', 'richtext', 'uid', 'url' => 'string',
                'email'      => 'email',
                'integer', 'biginteger' => 'integer',
                'decimal', 'float'      => 'numeric',
                'boolean'    => 'boolean',
                'date'       => 'date',
                'datetime'   => 'date',
                default      => 'string',
            };

            if (isset($field['maxLength'])) {
                $rule[] = 'max:' . $field['maxLength'];
            }

            if (isset($field['min'])) {
                $rule[] = 'min:' . $field['min'];
            }

            $rules[$name] = implode('|', $rule);
        }

        return $rules;
    }

    private function notFound(string $name): JsonResponse
    {
        return response()->json(['error' => "Content type [{$name}] not found."], 404);
    }
}
