<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComponentService;
use App\Services\ContentTypeService;
use Illuminate\Http\Request;

class ContentTypeController extends Controller
{
    public function __construct(
        private ContentTypeService $service,
        private ComponentService $componentService,
    ) {}

    public function index()
    {
        $contentTypes = $this->service->all();
        $components   = $this->componentService->all();

        return view('talos.content-type-builder.index', compact('contentTypes', 'components'));
    }

    public function create()
    {
        $components = $this->componentService->grouped();

        return view('talos.content-type-builder.create', compact('components'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'info.displayName'  => 'required|string|max:128',
            'info.singularName' => 'required|string|max:128|regex:/^[a-z0-9_]+$/',
            'info.pluralName'   => 'required|string|max:128|regex:/^[a-z0-9_]+$/',
            'kind'              => 'required|in:collectionType,singleType',
        ]);

        try {
            $schema = $this->service->create($request->all());
            return redirect()
                ->route('talos.content-type-builder.edit', ['uid' => $schema['__uid'] ?? 'api.' . $request->input('info.singularName')])
                ->with('success', 'Content type created. Now add your fields.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(string $uid)
    {
        $contentType = $this->service->find($uid);

        if (! $contentType) {
            abort(404);
        }

        $components   = $this->componentService->grouped();
        $contentTypes = $this->service->all();

        return view('talos.content-type-builder.edit', compact('contentType', 'components', 'contentTypes', 'uid'));
    }

    public function update(Request $request, string $uid)
    {
        try {
            $this->service->update($uid, $request->all());
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function apiSettings(string $uid)
    {
        $contentType = $this->service->find($uid);

        if (! $contentType) {
            abort(404);
        }

        $apiFields = $contentType['apiFields'] ?? null;

        return view('talos.content-type-builder.api-settings', compact('contentType', 'uid', 'apiFields'));
    }

    public function saveApiSettings(Request $request, string $uid)
    {
        $contentType = $this->service->find($uid);

        if (! $contentType) {
            abort(404);
        }

        $fields = $request->has('fields') ? array_values($request->input('fields')) : null;
        $this->service->saveApiFields($uid, $fields);

        return back()->with('success', 'API field settings saved.');
    }

    public function destroy(string $uid)
    {
        try {
            $this->service->delete($uid);
            return redirect()
                ->route('talos.content-type-builder.index')
                ->with('success', 'Content type deleted.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
