<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TalosUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'talos_users';

    protected $fillable = [
        'firstname', 'lastname', 'email', 'password',
        'is_active', 'is_super_admin', 'role_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'is_active'      => 'boolean',
        'is_super_admin' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(TalosRole::class, 'role_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->firstname} {$this->lastname}";
    }

    public function canDo(string $contentType, string $action): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        if (! $this->role) {
            return false;
        }

        $permissions = $this->role->permissions ?? [];

        return in_array("{$contentType}.{$action}", $permissions, true);
    }
}
