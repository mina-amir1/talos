<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalosMedia;
use App\Services\ContentTypeService;
use App\Services\DynamicModelService;
use Illuminate\Http\Request;
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

        // Single types go straight to the edit/create form
        if (($contentType['kind'] ?? 'collectionType') === 'singleType') {
            $model = $this->modelService->make($uid);
            $entry = $model->newQuery()->first();

            if ($entry) {
                return redirect()->route('talos.content.edit', ['uid' => $uid, 'id' => $entry->id]);
            }

            return redirect()->route('talos.content.create', ['uid' => $uid]);
        }

        $model   = $this->modelService->make($uid);
        // Admin always sees all entries (drafts + published). The public API hides drafts.
        $entries = $model->newQuery()->latest()->paginate(config('talos.default_page_size'));

        return view('talos.content.index', compact('contentType', 'entries', 'uid'));
    }

    public function create(string $uid)
    {
        $contentType = $this->typeService->find($uid);

        if (! $contentType) {
            abort(404);
        }

        $components      = app(\App\Services\ComponentService::class)->all();
        $mediaItems      = TalosMedia::latest()->get();
        $relationOptions = $this->loadRelationOptions($contentType['attributes'] ?? []);

        return view('talos.content.form', compact('contentType', 'uid', 'components', 'mediaItems', 'relationOptions'));
    }

    public function store(Request $request, string $uid)
    {
        $contentType = $this->typeService->find($uid);

        if (! $contentType) {
            abort(404);
        }

        $data = $this->processFormData($request, $contentType['attributes'] ?? []);

        if ($contentType['options']['draftAndPublish'] ?? false) {
            $data['published_at'] = $request->boolean('publish') ? now() : null;
        }

        $data['created_by'] = session('talos_user_id');
        $data['updated_by'] = session('talos_user_id');

        $model = $this->modelService->make($uid);
        $entry = $model->newQuery()->create($data);

        // Single types go back to their edit form, not the list
        if (($contentType['kind'] ?? 'collectionType') === 'singleType') {
            return redirect()
                ->route('talos.content.edit', ['uid' => $uid, 'id' => $entry->id])
                ->with('success', 'Entry saved.');
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

        $model           = $this->modelService->make($uid);
        $entry           = $model->newQuery()->findOrFail($id);
        $components      = app(\App\Services\ComponentService::class)->all();
        $mediaItems      = TalosMedia::latest()->get();
        $relationOptions = $this->loadRelationOptions($contentType['attributes'] ?? []);

        return view('talos.content.form', compact('contentType', 'uid', 'entry', 'components', 'mediaItems', 'relationOptions'));
    }

    public function update(Request $request, string $uid, int $id)
    {
        $contentType = $this->typeService->find($uid);

        if (! $contentType) {
            abort(404);
        }

        $data = $this->processFormData($request, $contentType['attributes'] ?? []);

        if ($contentType['options']['draftAndPublish'] ?? false) {
            $data['published_at'] = $request->boolean('publish') ? now() : null;
        }

        $data['updated_by'] = session('talos_user_id');

        $model = $this->modelService->make($uid);
        $model->newQuery()->findOrFail($id)->update($data);

        // Single types stay on the edit form
        if (($contentType['kind'] ?? 'collectionType') === 'singleType') {
            return redirect()
                ->route('talos.content.edit', ['uid' => $uid, 'id' => $id])
                ->with('success', 'Entry saved.');
        }

        return redirect()
            ->route('talos.content.index', ['uid' => $uid])
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
                'json', 'component', 'dynamiczone', 'repeater' => is_string($value) ? json_decode($value, true) : $value,
                default      => $value,
            };
        }

        return array_filter($data, fn($v) => $v !== null);
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

            // Find the first string/text attribute to use as a display label
            // Prefer clean text fields; fall back to richtext/blocks (HTML stripped later)
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
}
