<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FileRejectedException;
use App\Http\Controllers\Controller;
use App\Jobs\DispatchWebhook;
use App\Services\ContentTypeService;
use App\Services\DynamicModelService;
use App\Services\EntryTransformerService;
use App\Services\FileUploadService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContentApiController extends Controller
{
    public function __construct(
        private ContentTypeService      $typeService,
        private DynamicModelService     $modelService,
        private EntryTransformerService $transformer,
        private NotificationService     $notifications,
        private FileUploadService       $uploads,
    ) {}

    /**
     * Resolve any uploaded files on `file`-type fields into TalosFile IDs, so a single
     * multipart request can carry both the entry's fields and the file(s) to attach.
     * Existing `file`/`media` values sent as plain IDs (the two-step flow) pass through untouched.
     *
     * Returns ['values' => [field => id|ids], 'error' => ?JsonResponse]. Resolved values are
     * merged in by the caller rather than written back onto the request — Request::all() re-derives
     * files from allFiles(), which memoizes on first access, so mutating the request in place here
     * would not reliably stick.
     */
    private function resolveFileUploads(Request $request, array $attributes): array
    {
        $values = [];

        foreach ($attributes as $name => $field) {
            if (($field['type'] ?? null) !== 'file' || ! $request->hasFile($name)) {
                continue;
            }

            $isMultiple = $field['multiple'] ?? false;
            $uploaded   = $request->file($name);
            $files      = is_array($uploaded) ? $uploaded : [$uploaded];

            try {
                $ids = array_map(fn ($f) => $this->uploads->store($f)->id, $files);
            } catch (FileRejectedException $e) {
                return ['values' => [], 'error' => response()->json(['error' => $e->getMessage()], 422)];
            }

            $values[$name] = $isMultiple ? $ids : $ids[0];
        }

        return ['values' => $values, 'error' => null];
    }

    public function index(Request $request, string $name): JsonResponse
    {
        $contentType = $this->typeService->resolveByName($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        $uid        = $contentType['__uid'];
        $model      = $this->modelService->make($uid);
        $locale     = $this->requestLocale($request);
        $i18n       = (bool) ($contentType['options']['i18n'] ?? false);
        $attributes = $contentType['attributes'] ?? [];
        $apiFields  = $contentType['apiFields'] ?? null;

        if (($contentType['kind'] ?? 'collectionType') === 'singleType') {
            $query = $model->newQuery();

            if ($contentType['options']['draftAndPublish'] ?? false) {
                $query->whereNotNull('published_at');
            }

            if ($i18n) {
                $entry = (clone $query)->where('locale', $locale)->first()
                    ?? (clone $query)->where('locale', config('talos.default_locale'))->first();
            } else {
                $entry = $query->first();
            }

            if (! $entry) {
                return response()->json(['data' => null]);
            }

            $data = $this->transformer->transform([$entry->toArray()], $attributes, $apiFields, $locale)[0];

            return response()->json(['data' => $data]);
        }

        $query = $model->newQuery();

        if ($contentType['options']['draftAndPublish'] ?? false) {
            $query->whereNotNull('published_at');
        }

        if ($i18n) {
            $query->where('locale', $locale);
        }

        if ($request->has('filters')) {
            foreach ($request->filters as $field => $value) {
                $query->where($field, $value);
            }
        }

        if ($request->filled('sort')) {
            [$field, $dir] = array_pad(explode(':', $request->sort), 2, 'asc');
            $query->orderBy($field, $dir);
        } elseif ($contentType['options']['manualOrder'] ?? false) {
            $query->orderBy('sort_order')->orderBy('id');
        } else {
            $query->latest();
        }

        $pageSize  = min((int) ($request->pagination['pageSize'] ?? config('talos.default_page_size')), 100);
        $page      = (int) ($request->pagination['page'] ?? 1);
        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);
        $items     = $this->transformer->transform(
            collect($paginator->items())->map->toArray()->all(),
            $attributes,
            $apiFields,
            $locale
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

    public function show(Request $request, string $name, string $id): JsonResponse
    {
        $contentType = $this->typeService->resolveByName($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        $uid        = $contentType['__uid'];
        $model      = $this->modelService->make($uid);
        $locale     = $this->requestLocale($request);
        $i18n       = (bool) ($contentType['options']['i18n'] ?? false);
        $entry      = $this->resolveEntry($model, $id, $i18n, $locale);

        if ($i18n && $entry->locale !== $locale && $entry->localizations_id) {
            $localized = $model->newQuery()
                ->where('localizations_id', $entry->localizations_id)
                ->where('locale', $locale)
                ->first()
                ?? $model->newQuery()
                    ->where('localizations_id', $entry->localizations_id)
                    ->where('locale', config('talos.default_locale'))
                    ->first();

            if ($localized) {
                $entry = $localized;
            }
        }

        $data = $this->transformer->transform(
            [$entry->toArray()],
            $contentType['attributes'] ?? [],
            $contentType['apiFields'] ?? null,
            $locale
        )[0];

        return response()->json(['data' => $data]);
    }

    public function store(Request $request, string $name): JsonResponse
    {
        $contentType = $this->typeService->resolveByName($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        $uid        = $contentType['__uid'];
        $attributes = $contentType['attributes'] ?? [];

        $upload = $this->resolveFileUploads($request, $attributes);
        if ($upload['error']) {
            return $upload['error'];
        }

        $rules = $this->typeService->buildValidationRules($attributes);
        foreach (array_keys($upload['values']) as $field) {
            unset($rules[$field], $rules["$field.*"]);
        }

        $validated = array_merge($request->validate($rules), $upload['values']);
        $model     = $this->modelService->make($uid);

        if (($contentType['kind'] ?? 'collectionType') === 'singleType') {
            $entry = $model->newQuery()->first();
            $entry ? $entry->update($validated) : $entry = $model->newQuery()->create($validated);

            $event     = 'entry.update';
            $entryData = $entry->fresh()->toArray();
            DispatchWebhook::dispatch($event, $uid, $entryData);
            $this->notifications->dispatchEntryEvent($event, $uid, $entryData);

            return response()->json(['data' => $entry]);
        }

        $entry     = $model->newQuery()->create($validated);
        $entryData = $entry->fresh()->toArray();
        DispatchWebhook::dispatch('entry.create', $uid, $entryData);
        $this->notifications->dispatchEntryEvent('entry.create', $uid, $entryData);

        return response()->json(['data' => $entry], 201);
    }

    public function update(Request $request, string $name, string $id): JsonResponse
    {
        $contentType = $this->typeService->resolveByName($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        $uid = $contentType['__uid'];

        $upload = $this->resolveFileUploads($request, $contentType['attributes'] ?? []);
        if ($upload['error']) {
            return $upload['error'];
        }

        $model = $this->modelService->make($uid);
        $entry = $this->resolveEntry($model, $id, (bool) ($contentType['options']['i18n'] ?? false), $this->requestLocale($request));
        $entry->update(array_merge($request->all(), $upload['values']));

        $entryData = $entry->fresh()->toArray();
        DispatchWebhook::dispatch('entry.update', $uid, $entryData);
        $this->notifications->dispatchEntryEvent('entry.update', $uid, $entryData);

        return response()->json(['data' => $entry]);
    }

    public function destroy(Request $request, string $name, string $id): JsonResponse
    {
        $contentType = $this->typeService->resolveByName($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        $uid   = $contentType['__uid'];
        $model = $this->modelService->make($uid);
        $this->resolveEntry($model, $id, (bool) ($contentType['options']['i18n'] ?? false), $this->requestLocale($request))->delete();

        DispatchWebhook::dispatch('entry.delete', $uid, ['id' => $id]);
        $this->notifications->dispatchEntryEvent('entry.delete', $uid, ['id' => $id]);

        return response()->json(['data' => null], 200);
    }

    public function updateSingle(Request $request, string $name): JsonResponse
    {
        $contentType = $this->typeService->resolveByName($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        if (($contentType['kind'] ?? 'collectionType') !== 'singleType') {
            return response()->json(['error' => "Use PUT /{$name}/{id} for collection types."], 400);
        }

        $uid        = $contentType['__uid'];
        $attributes = $contentType['attributes'] ?? [];

        $upload = $this->resolveFileUploads($request, $attributes);
        if ($upload['error']) {
            return $upload['error'];
        }

        $model = $this->modelService->make($uid);
        $entry = $model->newQuery()->first();

        if (! $entry) {
            $rules = $this->typeService->buildValidationRules($attributes);
            foreach (array_keys($upload['values']) as $field) {
                unset($rules[$field], $rules["$field.*"]);
            }

            $validated = array_merge($request->validate($rules), $upload['values']);
            $entry     = $model->newQuery()->create($validated);
        } else {
            $entry->update(array_merge($request->all(), $upload['values']));
        }

        return response()->json(['data' => $entry]);
    }

    public function destroySingle(string $name): JsonResponse
    {
        $contentType = $this->typeService->resolveByName($name);

        if (! $contentType) {
            return $this->notFound($name);
        }

        if (($contentType['kind'] ?? 'collectionType') !== 'singleType') {
            return response()->json(['error' => "Use DELETE /{$name}/{id} for collection types."], 400);
        }

        $this->modelService->make($contentType['__uid'])->newQuery()->delete();

        return response()->json(['data' => null], 200);
    }

    private function resolveEntry(\Illuminate\Database\Eloquent\Model $model, string $id, bool $i18n, string $locale): \Illuminate\Database\Eloquent\Model
    {
        if (is_numeric($id)) {
            return $model->newQuery()->findOrFail((int) $id);
        }

        $query = $model->newQuery()->where('slug', $id);

        if ($i18n) {
            $entry = (clone $query)->where('locale', $locale)->first()
                ?? (clone $query)->where('locale', config('talos.default_locale'))->first();
        } else {
            $entry = $query->first();
        }

        return $entry ?? abort(404);
    }

    private function requestLocale(Request $request): string
    {
        return $request->input('locale') ?? $request->input('lang') ?? config('talos.default_locale');
    }

    private function notFound(string $name): JsonResponse
    {
        return response()->json(['error' => "Content type [{$name}] not found."], 404);
    }
}
