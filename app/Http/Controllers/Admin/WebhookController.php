<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TalosWebhook;
use App\Services\ContentTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WebhookController extends Controller
{
    public const EVENTS = [
        'entry.create'    => 'Entry Created',
        'entry.update'    => 'Entry Updated',
        'entry.delete'    => 'Entry Deleted',
        'entry.publish'   => 'Entry Published',
        'entry.unpublish' => 'Entry Unpublished',
    ];

    public function index(Request $request, ContentTypeService $types)
    {
$webhooks     = TalosWebhook::latest()->get();
        $contentTypes = collect($types->all())->mapWithKeys(fn($t) => [
            $t['__uid'] => $t['info']['displayName'] ?? $t['__uid'],
        ]);
        $events       = self::EVENTS;

        return view('talos.settings.webhooks', compact('webhooks', 'contentTypes', 'events'));
    }

    public function store(Request $request)
    {
$request->validate([
            'name'            => 'required|string|max:100',
            'url'             => 'required|url',
            'events'          => 'required|array|min:1',
            'events.*'        => 'in:entry.create,entry.update,entry.delete,entry.publish,entry.unpublish',
            'content_types'   => 'nullable|array',
            'secret'          => 'nullable|string|max:255',
        ]);

        TalosWebhook::create([
            'name'          => $request->name,
            'url'           => $request->url,
            'events'        => $request->events,
            'content_types' => $request->content_types ?? [],
            'secret'        => $request->secret ?: null,
            'is_active'     => true,
        ]);

        return back()->with('success', 'Webhook created.');
    }

    public function update(Request $request, int $id)
    {
$request->validate([
            'name'            => 'required|string|max:100',
            'url'             => 'required|url',
            'events'          => 'required|array|min:1',
            'events.*'        => 'in:entry.create,entry.update,entry.delete,entry.publish,entry.unpublish',
            'content_types'   => 'nullable|array',
            'secret'          => 'nullable|string|max:255',
        ]);

        $webhook = TalosWebhook::findOrFail($id);

        $webhook->update([
            'name'          => $request->name,
            'url'           => $request->url,
            'events'        => $request->events,
            'content_types' => $request->content_types ?? [],
            'secret'        => $request->filled('secret') ? $request->secret : $webhook->secret,
        ]);

        return back()->with('success', 'Webhook updated.');
    }

    public function destroy(Request $request, int $id)
    {
        TalosWebhook::findOrFail($id)->delete();
        return response()->json(['deleted' => true]);
    }

    public function toggle(Request $request, int $id)
    {
$webhook = TalosWebhook::findOrFail($id);
        $webhook->update(['is_active' => ! $webhook->is_active]);

        return response()->json(['is_active' => $webhook->is_active]);
    }

    public function test(Request $request, int $id)
    {
$webhook = TalosWebhook::findOrFail($id);

        $body = json_encode([
            'event'     => 'ping',
            'uid'       => null,
            'createdAt' => now()->toIso8601String(),
            'data'      => ['message' => 'Test ping from Talos.'],
        ]);

        $signature = hash_hmac('sha256', $body, $webhook->secret ?? '');

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type'      => 'application/json',
                    'X-Talos-Event'     => 'ping',
                    'X-Talos-Signature' => 'sha256=' . $signature,
                ])
                ->post($webhook->url, json_decode($body, true));

            return response()->json(['ok' => $response->successful(), 'status' => $response->status()]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

}
