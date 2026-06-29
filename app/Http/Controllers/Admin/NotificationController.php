<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalosNotificationRule;
use App\Services\ContentTypeService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public const EVENTS = [
        'entry.create'    => 'Entry Created',
        'entry.update'    => 'Entry Updated',
        'entry.delete'    => 'Entry Deleted',
        'entry.publish'   => 'Entry Published',
        'entry.unpublish' => 'Entry Unpublished',
    ];

    public function index(ContentTypeService $types)
    {
        $rules        = TalosNotificationRule::latest()->get();
        $contentTypes = collect($types->all())->mapWithKeys(fn($t) => [
            $t['__uid'] => $t['info']['displayName'] ?? $t['__uid'],
        ]);
        $events = self::EVENTS;

        return view('talos.settings.notifications', compact('rules', 'contentTypes', 'events'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'events'           => 'required|array|min:1',
            'events.*'         => 'in:entry.create,entry.update,entry.delete,entry.publish,entry.unpublish',
            'content_type_uid' => 'nullable|string',
            'recipients'       => 'required|string',
            'fields'           => 'nullable|array',
            'fields.*'         => 'string',
        ]);

        TalosNotificationRule::create([
            'name'             => $request->name,
            'events'           => $request->input('events', []),
            'content_type_uid' => $request->filled('content_type_uid') ? $request->content_type_uid : null,
            'recipients'       => $this->parseRecipients($request->recipients),
            'fields'           => $request->has('fields') ? array_values($request->fields) : null,
            'is_active'        => true,
        ]);

        return back()->with('success', 'Notification rule created.');
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'events'           => 'required|array|min:1',
            'events.*'         => 'in:entry.create,entry.update,entry.delete,entry.publish,entry.unpublish',
            'content_type_uid' => 'nullable|string',
            'recipients'       => 'required|string',
            'fields'           => 'nullable|array',
            'fields.*'         => 'string',
        ]);

        TalosNotificationRule::findOrFail($id)->update([
            'name'             => $request->name,
            'events'           => $request->input('events', []),
            'content_type_uid' => $request->filled('content_type_uid') ? $request->content_type_uid : null,
            'recipients'       => $this->parseRecipients($request->recipients),
            'fields'           => $request->has('fields') ? array_values($request->fields) : null,
        ]);

        return back()->with('success', 'Notification rule updated.');
    }

    public function destroy(int $id)
    {
        TalosNotificationRule::findOrFail($id)->delete();

        return response()->json(['deleted' => true]);
    }

    public function toggle(int $id)
    {
        $rule = TalosNotificationRule::findOrFail($id);
        $rule->update(['is_active' => ! $rule->is_active]);

        return response()->json(['is_active' => $rule->is_active]);
    }

    private function parseRecipients(string $raw): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/[\r\n,]+/', $raw)),
            fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        ));
    }
}
