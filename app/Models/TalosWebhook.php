<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TalosWebhook extends Model
{
    protected $fillable = ['name', 'url', 'events', 'content_types', 'secret', 'is_active'];

    protected $casts = [
        'events'        => 'array',
        'content_types' => 'array',
        'is_active'     => 'boolean',
    ];

    public function matchesEvent(string $event, string $uid): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! in_array($event, $this->events ?? [])) {
            return false;
        }

        $types = $this->content_types ?? [];

        return empty($types) || in_array($uid, $types);
    }
}
