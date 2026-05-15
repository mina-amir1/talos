<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComponentService;
use Illuminate\Http\Request;

class ComponentController extends Controller
{
    public function __construct(private ComponentService $service) {}

    public function index()
    {
        $grouped = $this->service->grouped();

        return view('talos.components.index', compact('grouped'));
    }

    public function create()
    {
        return view('talos.components.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'info.displayName' => 'required|string|max:128',
            'category'         => 'required|string|max:64|regex:/^[a-z0-9_]+$/',
        ]);

        $component = $this->service->create($request->all());

        return redirect()
            ->route('talos.components.edit', ['uid' => $component['__uid']])
            ->with('success', 'Component created. Now add fields.');
    }

    public function edit(string $uid)
    {
        $component = $this->service->find($uid);

        if (! $component) {
            abort(404);
        }

        return view('talos.components.edit', compact('component', 'uid'));
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

    public function destroy(string $uid)
    {
        $this->service->delete($uid);

        return redirect()
            ->route('talos.components.index')
            ->with('success', 'Component deleted.');
    }
}
