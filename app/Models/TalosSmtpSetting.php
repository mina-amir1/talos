<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class TalosSmtpSetting extends Model
{
    protected $table = 'talos_smtp_settings';

    protected $fillable = [
        'host', 'port', 'encryption', 'username', 'password',
        'from_name', 'from_email', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port'      => 'integer',
    ];

    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? decrypt($value) : '',
            set: fn($value) => $value !== '' ? encrypt($value) : '',
        );
    }
}
