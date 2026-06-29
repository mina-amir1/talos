<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TalosNotificationRule extends Model
{
    protected $table = 'talos_notification_rules';

    protected $fillable = [
        'name', 'events', 'content_type_uid', 'recipients', 'fields', 'is_active',
    ];

    protected $casts = [
        'events'     => 'array',
        'recipients' => 'array',
        'fields'     => 'array',
        'is_active'  => 'boolean',
    ];

    public function matchesEvent(string $event, string $uid): bool
    {
        if (! $this->is_active) return false;
        if (! in_array($event, $this->events ?? [])) return false;
        if ($this->content_type_uid && $this->content_type_uid !== $uid) return false;

        return true;
    }
}
