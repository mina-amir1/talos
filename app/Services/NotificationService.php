<?php

namespace App\Services;

use App\Jobs\SendTalosEmail;
use App\Mail\EntryEventMail;
use App\Models\TalosNotificationRule;

class NotificationService
{

    public function dispatchEntryEvent(string $event, string $uid, array $entry): void
    {
        $rules = TalosNotificationRule::where('is_active', true)
            ->where('event', $event)
            ->where(function ($q) use ($uid) {
                $q->whereNull('content_type_uid')->orWhere('content_type_uid', $uid);
            })
            ->get();

        foreach ($rules as $rule) {
            $fields = $rule->fields
                ? array_intersect_key($entry, array_flip($rule->fields))
                : $this->filterSystemFields($entry);

            $mailable = new EntryEventMail($rule->name, $uid, $event, $fields);

            foreach ($rule->recipients as $email) {
                SendTalosEmail::dispatch($email, $mailable);
            }
        }
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
