<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchWebhook;
use App\Models\TalosMedia;
use App\Services\ComponentService;
use App\Services\ContentEntryService;
use App\Services\ContentTypeService;
use App\Services\DynamicModelService;
use App\Services\LocaleService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContentManagerController extends Controller
{
    public function __construct(
        private ContentTypeService   $typeService,
        private DynamicModelService  $modelService,
        private ContentEntryService  $entryService,
        private NotificationService  $notificationService,
    ) {}

    // ── Listing ───────────────────────────────────────────────────────────────

    public function index(string $uid)
    {
        $contentType = $this->typeService->find($uid);
        if (! $contentType) abort(404);

        $i18n    = (bool) ($contentType['options']['i18n'] ?? false);
        $locales = app(LocaleService::class)->all();
        $locale  = request('locale', config('talos.default_locale'));

        if (($contentType['kind'] ?? 'collectionType') === 'singleType') {
            $entry = $this->entryService->findSingleTypeEntry($uid, $i18n, $locale);

            return $entry
                ? redirect()->route('talos.content.edit', ['uid' => $uid, 'id' => $entry->id])
                : redirect()->route('talos.content.create', ['uid' => $uid, 'locale' => $locale]);
        }

        $manualOrder = (bool) ($contentType['options']['manualOrder'] ?? false);
        $query       = $this->modelService->make($uid)->newQuery();

        if ($i18n)        { $query->where('locale', $locale); }
        if ($manualOrder) { $query->orderBy('sort_order')->orderBy('id'); }
        else              { $query->latest(); }

        $entries = $query->paginate(config('talos.default_page_size', 25));

        return view('talos.content.index', compact(
            'contentType', 'entries', 'uid', 'i18n', 'locale', 'locales', 'manualOrder'
        ));
    }

    // ── Create / Store ────────────────────────────────────────────────────────

    public function create(string $uid)
    {
        $contentType = $this->typeService->find($uid);
        if (! $contentType) abort(404);

        $i18n    = (bool) ($contentType['options']['i18n'] ?? false);
        $locales = app(LocaleService::class)->all();
        $locale  = request('locale', config('talos.default_locale'));

        return view('talos.content.form', [
            'contentType'     => $contentType,
            'uid'             => $uid,
            'i18n'            => $i18n,
            'locale'          => $locale,
            'locales'         => $locales,
            'components'      => app(ComponentService::class)->all(),
            'mediaItems'      => TalosMedia::latest()->get(),
            'relationOptions' => $this->entryService->loadRelationOptions($contentType['attributes'] ?? []),
        ]);
    }

    public function store(Request $request, string $uid)
    {
        $contentType = $this->typeService->find($uid);
        if (! $contentType) abort(404);

        $attributes = $contentType['attributes'] ?? [];
        $data       = $this->entryService->processFormData($request, $attributes);

        $validator = $this->entryService->makeValidator($data, $attributes);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $i18n         = (bool) ($contentType['options']['i18n'] ?? false);
        $isSingleType = ($contentType['kind'] ?? 'collectionType') === 'singleType';

        if ($contentType['options']['draftAndPublish'] ?? false) {
            $user                 = $request->attributes->get('talos_user');
            $data['published_at'] = $request->boolean('publish') && $this->entryService->canPublish($user, $uid)
                ? now()
                : null;
        }

        if ($i18n) {
            $data['locale']           = $request->input('locale', config('talos.default_locale'));
            $data['localizations_id'] = $request->input('localizations_id') ?: null;
        }

        $isTranslation = $i18n && $request->filled('localizations_id');
        if (! $isSingleType && ! $isTranslation && $request->filled('slug')) {
            $data['slug'] = Str::slug($request->input('slug'));
        }

        $data['created_by'] = session('talos_user_id');
        $data['updated_by'] = session('talos_user_id');

        $model = $this->modelService->make($uid);

        if ($contentType['options']['manualOrder'] ?? false) {
            $data['sort_order'] = ($model->newQuery()->max('sort_order') ?? 0) + 1;
        }

        $entry = $model->newQuery()->create($data);

        if ($i18n && ! $entry->localizations_id) {
            $entry->update(['localizations_id' => $entry->id]);
        }

        $entryData = $entry->fresh()->toArray();
        DispatchWebhook::dispatch('entry.create', $uid, $entryData);
        $this->notificationService->dispatchEntryEvent('entry.create', $uid, $entryData);

        if ($isSingleType) {
            return redirect()->route('talos.content.edit', ['uid' => $uid, 'id' => $entry->id])
                ->with('success', 'Entry saved.');
        }

        if ($i18n) {
            return redirect()->route('talos.content.edit', ['uid' => $uid, 'id' => $entry->id])
                ->with('success', 'Entry created. Add translations using the panel on the right.');
        }

        return redirect()->route('talos.content.index', ['uid' => $uid])
            ->with('success', 'Entry created successfully.');
    }

    // ── Edit / Update ─────────────────────────────────────────────────────────

    public function edit(string $uid, int $id)
    {
        $contentType = $this->typeService->find($uid);
        if (! $contentType) abort(404);

        $i18n    = (bool) ($contentType['options']['i18n'] ?? false);
        $locales = app(LocaleService::class)->all();
        $model   = $this->modelService->make($uid);
        $entry   = $model->newQuery()->findOrFail($id);
        $locale  = $i18n
            ? ($entry->locale ?? config('talos.default_locale'))
            : config('talos.default_locale');

        $siblings = [];
        if ($i18n && $entry->localizations_id) {
            $siblings = $model->newQuery()
                ->where('localizations_id', $entry->localizations_id)
                ->where('id', '!=', $entry->id)
                ->get(['id', 'locale'])
                ->keyBy('locale')
                ->toArray();
        }

        return view('talos.content.form', [
            'contentType'     => $contentType,
            'uid'             => $uid,
            'entry'           => $entry,
            'i18n'            => $i18n,
            'locale'          => $locale,
            'locales'         => $locales,
            'siblings'        => $siblings,
            'components'      => app(ComponentService::class)->all(),
            'mediaItems'      => TalosMedia::latest()->get(),
            'relationOptions' => $this->entryService->loadRelationOptions($contentType['attributes'] ?? []),
        ]);
    }

    public function update(Request $request, string $uid, int $id)
    {
        $contentType = $this->typeService->find($uid);
        if (! $contentType) abort(404);

        $attributes = $contentType['attributes'] ?? [];
        $data       = $this->entryService->processFormData($request, $attributes);

        $validator = $this->entryService->makeValidator($data, $attributes);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $i18n         = (bool) ($contentType['options']['i18n'] ?? false);
        $isSingleType = ($contentType['kind'] ?? 'collectionType') === 'singleType';

        if ($contentType['options']['draftAndPublish'] ?? false) {
            $user                 = $request->attributes->get('talos_user');
            $data['published_at'] = $request->boolean('publish') && $this->entryService->canPublish($user, $uid)
                ? now()
                : null;
        }

        $data['updated_by'] = session('talos_user_id');

        $model = $this->modelService->make($uid);
        $entry = $model->newQuery()->findOrFail($id);

        if (! $isSingleType && $request->filled('slug')) {
            $isRoot = ! $i18n || ! $entry->localizations_id || $entry->id === $entry->localizations_id;
            if ($isRoot) {
                $newSlug      = Str::slug($request->input('slug'));
                $data['slug'] = $newSlug;

                if ($i18n && $newSlug && $entry->localizations_id) {
                    $model->newQuery()
                        ->where('localizations_id', $entry->localizations_id)
                        ->where('id', '!=', $entry->id)
                        ->update(['slug' => $newSlug]);
                }
            }
        }

        $entry->update($data);

        $entryData = $entry->fresh()->toArray();
        DispatchWebhook::dispatch('entry.update', $uid, $entryData);
        $this->notificationService->dispatchEntryEvent('entry.update', $uid, $entryData);

        if ($isSingleType) {
            return redirect()->route('talos.content.edit', ['uid' => $uid, 'id' => $id])
                ->with('success', 'Entry saved.');
        }

        $locale = $i18n ? $request->input('locale', config('talos.default_locale')) : null;

        return redirect()
            ->route('talos.content.index', array_filter(['uid' => $uid, 'locale' => $locale]))
            ->with('success', 'Entry updated successfully.');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function destroy(string $uid, int $id)
    {
        $this->modelService->make($uid)->newQuery()->findOrFail($id)->delete();

        DispatchWebhook::dispatch('entry.delete', $uid, ['id' => $id]);
        $this->notificationService->dispatchEntryEvent('entry.delete', $uid, ['id' => $id]);

        return redirect()->route('talos.content.index', ['uid' => $uid])
            ->with('success', 'Entry deleted.');
    }

    // ── Publish / Unpublish ───────────────────────────────────────────────────

    public function publish(string $uid, int $id)
    {
        $entry = $this->modelService->make($uid)->newQuery()->findOrFail($id);
        $entry->update(['published_at' => now()]);

        $entryData = $entry->fresh()->toArray();
        DispatchWebhook::dispatch('entry.publish', $uid, $entryData);
        $this->notificationService->dispatchEntryEvent('entry.publish', $uid, $entryData);

        return back()->with('success', 'Entry published.');
    }

    public function unpublish(string $uid, int $id)
    {
        $entry = $this->modelService->make($uid)->newQuery()->findOrFail($id);
        $entry->update(['published_at' => null]);

        $entryData = $entry->fresh()->toArray();
        DispatchWebhook::dispatch('entry.unpublish', $uid, $entryData);
        $this->notificationService->dispatchEntryEvent('entry.unpublish', $uid, $entryData);

        return back()->with('success', 'Entry unpublished.');
    }

    // ── Bulk Actions ──────────────────────────────────────────────────────────

    public function bulkDestroy(Request $request, string $uid)
    {
        $ids = $this->entryService->parseBulkIds($request);

        if (empty($ids)) {
            return back()->with('error', 'No entries selected.');
        }

        $this->modelService->make($uid)->newQuery()->whereIn('id', $ids)->delete();

        foreach ($ids as $id) {
            DispatchWebhook::dispatch('entry.delete', $uid, ['id' => (int) $id]);
        }

        return back()->with('success', $this->entryService->entryCount($ids, 'deleted'));
    }

    public function bulkPublish(Request $request, string $uid)
    {
        $contentType = $this->typeService->find($uid);

        if (! $contentType || ! ($contentType['options']['draftAndPublish'] ?? false)) {
            abort(404);
        }

        $user = $request->attributes->get('talos_user');
        if (! $this->entryService->canPublish($user, $uid)) abort(403);

        $ids = $this->entryService->parseBulkIds($request);

        if (empty($ids)) {
            return back()->with('error', 'No entries selected.');
        }

        $model = $this->modelService->make($uid);
        $model->newQuery()->whereIn('id', $ids)->update([
            'published_at' => now(),
            'updated_by'   => session('talos_user_id'),
        ]);

        foreach ($model->newQuery()->whereIn('id', $ids)->get() as $entry) {
            $entryData = $entry->toArray();
            DispatchWebhook::dispatch('entry.publish', $uid, $entryData);
            $this->notificationService->dispatchEntryEvent('entry.publish', $uid, $entryData);
        }

        return back()->with('success', $this->entryService->entryCount($ids, 'published'));
    }

    public function bulkUnpublish(Request $request, string $uid)
    {
        $contentType = $this->typeService->find($uid);

        if (! $contentType || ! ($contentType['options']['draftAndPublish'] ?? false)) {
            abort(404);
        }

        $ids = $this->entryService->parseBulkIds($request);

        if (empty($ids)) {
            return back()->with('error', 'No entries selected.');
        }

        $model = $this->modelService->make($uid);
        $model->newQuery()->whereIn('id', $ids)->update([
            'published_at' => null,
            'updated_by'   => session('talos_user_id'),
        ]);

        foreach ($model->newQuery()->whereIn('id', $ids)->get() as $entry) {
            $entryData = $entry->toArray();
            DispatchWebhook::dispatch('entry.unpublish', $uid, $entryData);
            $this->notificationService->dispatchEntryEvent('entry.unpublish', $uid, $entryData);
        }

        return back()->with('success', $this->entryService->entryCount($ids, 'unpublished'));
    }

    // ── Duplicate ─────────────────────────────────────────────────────────────

    public function duplicate(string $uid, int $id)
    {
        $contentType = $this->typeService->find($uid);
        if (! $contentType) abort(404);

        $model = $this->modelService->make($uid);
        $entry = $model->newQuery()->findOrFail($id);

        $data = collect($entry->toArray())
            ->except(['id', 'created_at', 'updated_at', 'created_by', 'updated_by', 'published_at', 'localizations_id', 'sort_order'])
            ->toArray();

        $data['created_by']   = session('talos_user_id');
        $data['updated_by']   = session('talos_user_id');
        $data['published_at'] = null;

        if ($contentType['options']['manualOrder'] ?? false) {
            $data['sort_order'] = ($model->newQuery()->max('sort_order') ?? 0) + 1;
        }

        if (! empty($data['slug'])) {
            $data['slug'] = $this->entryService->uniqueSlug($uid, $data['slug'] . '-copy');
        }

        $newEntry = $model->newQuery()->create($data);

        if (($contentType['options']['i18n'] ?? false) && ! $newEntry->localizations_id) {
            $newEntry->update(['localizations_id' => $newEntry->id]);
        }

        DispatchWebhook::dispatch('entry.create', $uid, $newEntry->fresh()->toArray());

        return redirect()->route('talos.content.index', ['uid' => $uid])
            ->with('success', "Entry duplicated — draft copy created (ID {$newEntry->id}).");
    }

    // ── Manual Ordering ───────────────────────────────────────────────────────

    public function reorder(Request $request, string $uid): \Illuminate\Http\JsonResponse
    {
        $contentType = $this->typeService->find($uid);

        if (! $contentType || ! ($contentType['options']['manualOrder'] ?? false)) {
            return response()->json(['error' => 'Manual ordering is not enabled for this collection.'], 422);
        }

        $ids     = $request->input('ids', []);
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = max(1, (int) $request->input('per_page', 25));

        if (empty($ids) || ! is_array($ids)) {
            return response()->json(['error' => 'Invalid payload.'], 422);
        }

        $model  = $this->modelService->make($uid);
        $all    = $model->newQuery()->orderBy('sort_order')->orderBy('id')->pluck('id')->toArray();
        $offset = ($page - 1) * $perPage;

        array_splice($all, $offset, count($ids), array_map('intval', $ids));

        foreach ($all as $idx => $rowId) {
            $model->newQuery()->where('id', $rowId)->update(['sort_order' => $idx + 1]);
        }

        return response()->json(['success' => true]);
    }

    public function move(Request $request, string $uid): \Illuminate\Http\JsonResponse
    {
        $contentType = $this->typeService->find($uid);

        if (! $contentType || ! ($contentType['options']['manualOrder'] ?? false)) {
            return response()->json(['error' => 'Manual ordering is not enabled for this collection.'], 422);
        }

        $id  = (int) $request->input('id');
        $pos = (int) $request->input('position');

        $model   = $this->modelService->make($uid);
        $all     = $model->newQuery()->orderBy('sort_order')->orderBy('id')->pluck('id')->toArray();
        $total   = count($all);
        $pos     = max(1, min($pos, $total));
        $current = array_search($id, $all);

        if ($current === false) {
            return response()->json(['error' => 'Entry not found.'], 404);
        }

        array_splice($all, $current, 1);
        array_splice($all, $pos - 1, 0, [$id]);

        foreach ($all as $idx => $rowId) {
            $model->newQuery()->where('id', $rowId)->update(['sort_order' => $idx + 1]);
        }

        return response()->json(['success' => true, 'position' => $pos]);
    }

    // ── i18n ──────────────────────────────────────────────────────────────────

    public function translate(Request $request, string $uid, int $id)
    {
        $contentType = $this->typeService->find($uid);

        if (! $contentType || ! ($contentType['options']['i18n'] ?? false)) {
            abort(404);
        }

        $newLocale = $request->input('locale');

        if (! $newLocale || ! in_array($newLocale, app(LocaleService::class)->all())) {
            return back()->withErrors(['error' => 'Invalid locale.']);
        }

        $model    = $this->modelService->make($uid);
        $source   = $model->newQuery()->findOrFail($id);
        $existing = $model->newQuery()
            ->where('localizations_id', $source->localizations_id)
            ->where('locale', $newLocale)
            ->first();

        if ($existing) {
            return redirect()->route('talos.content.edit', ['uid' => $uid, 'id' => $existing->id]);
        }

        $data = collect($source->toArray())
            ->except(['id', 'locale', 'localizations_id', 'created_at', 'updated_at', 'created_by', 'updated_by'])
            ->toArray();

        $data['locale']           = $newLocale;
        $data['localizations_id'] = $source->localizations_id;
        $data['created_by']       = session('talos_user_id');
        $data['updated_by']       = session('talos_user_id');

        if ($contentType['options']['draftAndPublish'] ?? false) {
            $data['published_at'] = null;
        }

        $entry = $model->newQuery()->create($data);

        return redirect()->route('talos.content.edit', ['uid' => $uid, 'id' => $entry->id])
            ->with('success', "Translation created for locale \"{$newLocale}\". Update the content below.");
    }

}
