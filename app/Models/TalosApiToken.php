<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TalosApiToken extends Model
{
    protected $table = 'talos_api_tokens';

    protected $fillable = [
        'name', 'token', 'type', 'permissions',
        'last_used_at', 'expires_at', 'created_by',
    ];

    protected $casts = [
        'permissions'  => 'array',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(TalosUser::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
