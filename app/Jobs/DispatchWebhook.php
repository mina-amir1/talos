<?php

namespace App\Jobs;

use App\Models\TalosWebhook;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;

class DispatchWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(
        public string $event,
        public string $uid,
        public array  $entry,
    ) {}

    public function handle(): void
    {
        $webhooks = TalosWebhook::where('is_active', true)->get()
            ->filter(fn($w) => $w->matchesEvent($this->event, $this->uid));

        if ($webhooks->isEmpty()) {
            return;
        }

        $body = json_encode([
            'event'     => $this->event,
            'uid'       => $this->uid,
            'createdAt' => now()->toIso8601String(),
            'data'      => $this->entry,
        ]);

        foreach ($webhooks as $webhook) {
            $signature = hash_hmac('sha256', $body, $webhook->secret ?? '');

            try {
                Http::timeout(10)
                    ->withHeaders([
                        'Content-Type'        => 'application/json',
                        'X-Talos-Event'       => $this->event,
                        'X-Talos-Signature'   => 'sha256=' . $signature,
                    ])
                    ->post($webhook->url, json_decode($body, true));
            } catch (\Throwable) {
                // One failing webhook must not prevent others from firing
            }
        }
    }
}
