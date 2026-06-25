<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalosMedia;
use App\Services\ContentTypeService;
use App\Services\DynamicModelService;
use App\Services\LocaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContentManagerController extends Controller
{
    public function __construct(
        private ContentTypeService $typeService,
        private DynamicModelService $modelService,
    ) {}

    public function index(string $uid)
    {
        $contentType = $this->typeService->find($uid);

        if (! $contentType) {
            abort(404);
        }

        $i18n    = (bool) ($contentType['options']['i18n'] ?? false);
        $locales = app(LocaleService::class)->all();
        $locale  = request('locale', config('talos.default_locale'));

        // Single types go straight to the edit/create form
        if (($contentType['kind'] ?? 'collectionType') === 'singleType') {
            $model = $this->modelService->make($uid);
            $query = $model->newQuery();
            if ($i18n) {
                $query->where('locale', $locale);
            }
            $entry = $query->first();

            if ($entry) {
                return redirect()->route('talos.content.edit', ['uid' => $uid, 'id' => $entry->id]);
            }

            return redirect()->route('talos.content.create', ['uid' => $uid, 'locale' => $locale]);
        }

        $model       = $this->modelService->make($uid);
        $manualOrder = (bool) ($contentType['options']['manualOrder'] ?? false);
        $query       = $model->newQuery();

        if ($i18n) {
            $query->where('locale', $locale);
        }

        if ($manualOrder) {
            $query->orderBy('sort_order')->orderBy('id');
        } else {
            $query->latest();
        }

        $entries = $query->paginate(config('talos.default_page_size', 25));

        return view('talos.content.index', compact('contentType', 'entries', 'uid', 'i18n', 'locale', 'locales', 'manualOrder'));
    }

    public function create(string $uid)
    {
        $contentType = $this->typeService->find($uid);

        if (! $contentType) {
            abort(404);
        }

        $i18n    = (bool) ($contentType['options']['i18n'] ?? false);
        $locales = app(LocaleService::class)->all();
        $locale  = request('locale', config('talos.default_locale'));

        $components      = app(\App\Services\ComponentService::class)->all();
        $mediaItems      = TalosMedia::latest()->get();
        $relationOptions = $this->loadRelationOptions($contentType['attributes'] ?? []);

        return view('talos.content.form', compact(
            'contentType', 'uid', 'components', 'mediaItems', 'relationOptions',
            'i18n', 'locale', 'locales'
        ));
    }

    public function store(Request $request, string $uid)
    {
        $contentType = $this->typeService->find($uid);

        if (! $contentType) {
            abort(404);
        }

        $i18n       = (bool) ($contentType['options']['i18n'] ?? false);
        $attributes = $contentType['attributes'] ?? [];
        $data       = $this->processFormData($request, $attributes);

        if ($fail = $this->validateContent($data, $attributes)) {
            return $fail;
        }

        if ($contentType['options']['draftAndPublish'] ?? false) {
            $wantsPublish = $request->boolean('publish') && $this->canPublish($request, $uid);
            $data['published_at'] = $wantsPublish ? now() : null;
        }

        if ($i18n) {
            $data['locale']           = $request->input('locale', config('talos.default_locale'));
            $data['localizations_id'] = $request->input('localizations_id') ?: null;
        }

        // Slug: collection types only; translations inherit from parent (localizations_id set)
        $isCollection = ($contentType['kind'] ?? 'collectionType') === 'collectionType';
        if ($isCollection) {
            $isTranslation = $i18n && $request->filled('localizations_id');
            if (! $isTranslation && $request->filled('slug')) {
                $data['slug'] = Str::slug($request->input('slug'));
            }
        }

        $data['created_by'] = session('talos_user_id');
        $data['updated_by'] = session('talos_user_id');

        $model = $this->modelService->make($uid);

        if ($contentType['options']['manualOrder'] ?? false) {
            $data['sort_order'] = ($model->newQuery()->max('sort_order') ?? 0) + 1;
        }

        $entry = $model->newQuery()->create($data);

        // First entry of this locale group — point localizations_id to itself
        if ($i18n && ! $entry->localizations_id) {
            $entry->update(['localizations_id' => $entry->id]);
        }

        if (($contentType['kind'] ?? 'collectionType') === 'singleType') {
            return redirect()
                ->route('talos.content.edit', ['uid' => $uid, 'id' => $entry->id])
                ->with('success', 'Entry saved.');
        }

        if ($i18n) {
            return redirect()
                ->route('talos.content.edit', ['uid' => $uid, 'id' => $entry->id])
                ->with('success', 'Entry created. Add translations using the panel on the right.');
        }

        return redirect()
            ->route('talos.content.index', ['uid' => $uid])
            ->with('success', 'Entry created successfully.');
    }

    public function edit(string $uid, int $id)
    {
        $contentType = $this->typeService->find($uid);

        if (! $contentType) {
            abort(404);
        }

        $i18n    = (bool) ($contentType['options']['i18n'] ?? false);
        $locales = app(LocaleService::class)->all();

        $model  = $this->modelService->make($uid);
        $entry  = $model->newQuery()->findOrFail($id);
        $locale = $i18n ? ($entry->locale ?? config('talos.default_locale')) : config('talos.default_locale');

        // Sibling translations (other locale versions of the same logical entry)
        $siblings = [];
        if ($i18n && $entry->localizations_id) {
            $siblings = $model->newQuery()
                ->where('localizations_id', $entry->localizations_id)
                ->where('id', '!=', $entry->id)
                ->get(['id', 'locale'])
                ->keyBy('locale')
                ->toArray();
        }

        $components      = app(\App\Services\ComponentService::class)->all();
        $mediaItems      = TalosMedia::latest()->get();
        $relationOptions = $this->loadRelationOptions($contentType['attributes'] ?? []);

        return view('talos.content.form', compact(
            'contentType', 'uid', 'entry', 'components', 'mediaItems', 'relationOptions',
            'i18n', 'locale', 'locales', 'siblings'
        ));
    }

    public function update(Request $request, string $uid, int $id)
    {
        $contentType = $this->typeService->find($uid);

        if (! $contentType) {
            abort(404);
        }

        $i18n       = (bool) ($contentType['options']['i18n'] ?? false);
        $attributes = $contentType['attributes'] ?? [];
        $data       = $this->processFormData($request, $attributes);

        if ($fail = $this->validateContent($data, $attributes)) {
            return $fail;
        }

        if ($contentType['options']['draftAndPublish'] ?? false) {
            $wantsPublish = $request->boolean('publish') && $this->canPublish($request, $uid);
            $data['published_at'] = $wantsPublish ? now() : null;
        }

        $data['updated_by'] = session('talos_user_id');

        $model = $this->modelService->make($uid);
        $entry = $model->newQuery()->findOrFail($id);

        // Slug: only update on root entries; sync to all translations
        if (($contentType['kind'] ?? 'collectionType') === 'collectionType' && $request->filled('slug')) {
            $isRoot = ! $i18n || ! $entry->localizations_id || $entry->id === $entry->localizations_id;
            if ($isRoot) {
                $newSlug = Str::slug($request->input('slug'));
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

        if (($contentType['kind'] ?? 'collectionType') === 'singleType') {
            return redirect()
                ->route('talos.content.edit', ['uid' => $uid, 'id' => $id])
                ->with('success', 'Entry saved.');
        }

        $locale = $i18n ? $request->input('locale', config('talos.default_locale')) : null;

        return redirect()
            ->route('talos.content.index', array_filter(['uid' => $uid, 'locale' => $locale]))
            ->with('success', 'Entry updated successfully.');
    }

    public function destroy(string $uid, int $id)
    {
        $model = $this->modelService->make($uid);
        $model->newQuery()->findOrFail($id)->delete();

        return redirect()
            ->route('talos.content.index', ['uid' => $uid])
            ->with('success', 'Entry deleted.');
    }

    public function publish(string $uid, int $id)
    {
        $model = $this->modelService->make($uid);
        $model->newQuery()->findOrFail($id)->update(['published_at' => now()]);

        return back()->with('success', 'Entry published.');
    }

    public function unpublish(string $uid, int $id)
    {
        $model = $this->modelService->make($uid);
        $model->newQuery()->findOrFail($id)->update(['published_at' => null]);

        return back()->with('success', 'Entry unpublished.');
    }

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

        $model  = $this->modelService->make($uid);
        $source = $model->newQuery()->findOrFail($id);

        // Check if this locale already exists for this entry group
        $existing = $model->newQuery()
            ->where('localizations_id', $source->localizations_id)
            ->where('locale', $newLocale)
            ->first();

        if ($existing) {
            return redirect()->route('talos.content.edit', ['uid' => $uid, 'id' => $existing->id]);
        }

        // Create a copy with the new locale — editor fills in the translated content
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

        return redirect()
            ->route('talos.content.edit', ['uid' => $uid, 'id' => $entry->id])
            ->with('success', 'Translation created for locale "' . $newLocale . '". Update the content below.');
    }

    private function processFormData(Request $request, array $attributes): array
    {
        $data = [];

        foreach ($attributes as $name => $field) {
            if ($field['type'] === 'media') {
                $raw = $request->input($name . '_id');
                $data[$name] = ($field['multiple'] ?? false)
                    ? (is_string($raw) ? json_decode($raw, true) : ($raw ?? []))
                    : $raw;
                continue;
            }

            if ($field['type'] === 'relation') {
                $isMultiple  = in_array($field['relation'] ?? 'manyToOne', ['oneToMany', 'manyToMany']);
                $raw         = $request->input($name);
                $data[$name] = $isMultiple ? array_filter((array) ($raw ?? []), 'is_numeric') : ($raw ?: null);
                continue;
            }

            $value = $request->input($name);

            $data[$name] = match ($field['type']) {
                'boolean'    => $request->boolean($name),
                'json', 'component', 'dynamiczone', 'repeater' => (is_string($value) && $value !== '') ? json_decode($value, true) : null,
                default      => $value,
            };
        }

        return $data;
    }

    private function loadRelationOptions(array $attributes): array
    {
        $options = [];

        foreach ($attributes as $name => $field) {
            if ($field['type'] !== 'relation' || empty($field['target'])) {
                continue;
            }

            $targetUid  = $field['target'];
            $targetType = $this->typeService->find($targetUid);

            if (! $targetType) {
                continue;
            }

            $entries = $this->modelService->make($targetUid)->newQuery()->latest()->get();

            $labelField = collect($targetType['attributes'] ?? [])
                ->filter(fn($a) => in_array($a['type'] ?? '', ['string', 'text', 'email', 'uid']))
                ->keys()
                ->first()
                ?? collect($targetType['attributes'] ?? [])
                    ->filter(fn($a) => in_array($a['type'] ?? '', ['richtext', 'blocks']))
                    ->keys()
                    ->first();

            $options[$name] = [
                'entries'    => $entries,
                'labelField' => $labelField,
                'multiple'   => in_array($field['relation'] ?? 'manyToOne', ['oneToMany', 'manyToMany']),
            ];
        }

        return $options;
    }

    private function validateContent(array $data, array $attributes): ?\Illuminate\Http\RedirectResponse
    {
        $rules    = [];
        $messages = [];

        foreach ($attributes as $name => $field) {
            $type = $field['type'] ?? '';

            // Skip types that don't have simple scalar validation
            if (in_array($type, ['media', 'relation', 'component', 'dynamiczone', 'richtext', 'json'])) {
                continue;
            }

            if ($type === 'enumeration' && !empty($field['multiple'])) {
                $rules[$name] = ($field['required'] ?? false) ? 'required|array' : 'nullable|array';
                if (!empty($field['enumValues'])) {
                    $values = array_filter(array_map('trim', explode("\n", $field['enumValues'])));
                    if (!empty($values)) {
                        $rules[$name . '.*'] = 'in:' . implode(',', $values);
                        $messages[$name . '.*.in'] = "The selected {$name} is invalid.";
                    }
                }
                continue;
            }

            if ($type === 'repeater') {
                foreach ($field['subFields'] ?? [] as $subName => $subField) {
                    $key      = "{$name}.*.{$subName}";
                    $subRules = ($subField['required'] ?? false) ? ['required'] : ['nullable'];
                    array_push($subRules, ...$this->typeRules($subField));
                    $rules[$key] = implode('|', $subRules);

                    if ($subField['required'] ?? false) {
                        $messages["{$key}.required"] = "The \"{$subName}\" field is required in every {$name} row.";
                        $messages["{$key}.in"]       = "The \"{$subName}\" value is not a valid option in every {$name} row.";
                    }
                }
                continue;
            }

            $fieldRules = ($field['required'] ?? false) ? ['required'] : ['nullable'];
            array_push($fieldRules, ...$this->typeRules($field));
            $rules[$name] = implode('|', $fieldRules);
        }

        if (empty($rules)) {
            return null;
        }

        $validator = Validator::make($data, $rules, $messages);

        return $validator->fails()
            ? back()->withErrors($validator)->withInput()
            : null;
    }

    private function typeRules(array $field): array
    {
        $rules = [];
        $type  = $field['type'] ?? '';

        if ($type === 'email') {
            $rules[] = 'email';
        } elseif (in_array($type, ['integer', 'biginteger'])) {
            $rules[] = 'integer';
        } elseif (in_array($type, ['decimal', 'float'])) {
            $rules[] = 'numeric';
        } elseif ($type === 'boolean') {
            $rules[] = 'boolean';
        } elseif (in_array($type, ['date', 'datetime'])) {
            $rules[] = 'date';
        } elseif ($type === 'url') {
            $rules[] = 'url';
        }

        if ($type === 'string' && ! empty($field['maxLength'])) {
            $rules[] = 'max:' . (int) $field['maxLength'];
        }

        if ($type === 'enumeration' && ! empty($field['enumValues'])) {
            $values = array_filter(array_map('trim', explode("\n", $field['enumValues'])));
            if (! empty($values)) {
                $rules[] = 'in:' . implode(',', $values);
            }
        }

        if (in_array($type, ['integer', 'biginteger', 'decimal', 'float'])) {
            if (isset($field['min'])) $rules[] = 'min:' . $field['min'];
            if (isset($field['max'])) $rules[] = 'max:' . $field['max'];
        }

        return $rules;
    }

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

        foreach ($all as $idx => $id) {
            $model->newQuery()->where('id', $id)->update(['sort_order' => $idx + 1]);
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

        $model = $this->modelService->make($uid);
        $all   = $model->newQuery()->orderBy('sort_order')->orderBy('id')->pluck('id')->toArray();
        $total = count($all);
        $pos   = max(1, min($pos, $total));

        $current = array_search($id, $all);

        if ($current === false) {
            return response()->json(['error' => 'Entry not found.'], 404);
        }

        array_splice($all, $current, 1);
        array_splice($all, $pos - 1, 0, [$id]);

        foreach ($all as $idx => $itemId) {
            $model->newQuery()->where('id', $itemId)->update(['sort_order' => $idx + 1]);
        }

        return response()->json(['success' => true, 'position' => $pos]);
    }

    private function canPublish(Request $request, string $uid): bool
    {
        $user = $request->attributes->get('talos_user');

        if (! $user) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        $allowed = $user->role?->permissions['content-manager'][$uid] ?? [];

        return in_array('publish', $allowed);
    }
}
