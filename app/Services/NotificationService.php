<?php

namespace App\Services;

use App\Jobs\SendTalosEmail;
use App\Mail\EntryEventMail;
use App\Models\TalosNotificationRule;
use Illuminate\Support\Str;

class NotificationService
{
    public function __construct(private ContentTypeService $typeService) {}

    public function dispatchEntryEvent(string $event, string $uid, array $entry): void
    {
        $rules = TalosNotificationRule::where('is_active', true)
            ->whereJsonContains('events', $event)
            ->where(function ($q) use ($uid) {
                $q->whereNull('content_type_uid')->orWhere('content_type_uid', $uid);
            })
            ->get();

        if ($rules->isEmpty()) {
            return;
        }

        $label = $this->contentTypeLabel($uid);

        foreach ($rules as $rule) {
            $fields = $rule->fields
                ? array_intersect_key($entry, array_flip($rule->fields))
                : $this->filterSystemFields($entry);

            $mailable = new EntryEventMail($rule->name, $label, $event, $fields);

            foreach ($rule->recipients as $email) {
                SendTalosEmail::dispatch($email, $mailable);
            }
        }
    }

    private function contentTypeLabel(string $uid): string
    {
        $displayName = $this->typeService->find($uid)['info']['displayName'] ?? null;

        if ($displayName) {
            return $displayName;
        }

        // Content type not found (e.g. deleted since the rule was created) — derive
        // a readable label from the uid rather than showing the raw "api.foo" form.
        return Str::of($uid)->after('.')->replace(['_', '-'], ' ')->title()->toString();
    }

    private function filterSystemFields(array $entry): array
    {
        $skip = ['created_by', 'updated_by', 'localizations_id', 'sort_order', 'published_at', 'created_at', 'updated_at'];

        return array_filter(
            $entry,
            fn($key) => ! in_array($key, $skip),
            ARRAY_FILTER_USE_KEY
        );
    }
}
