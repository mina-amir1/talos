<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class TalosSettings extends Model
{
    protected $table    = 'talos_settings';
    protected $fillable = ['key', 'value', 'encrypted'];

    protected static array $sensitiveKeys = [
        'r2_media_secret_key',
        'r2_backup_secret_key',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::where('key', $key)->first();

        if (! $row) {
            return $default;
        }

        if ($row->encrypted && $row->value) {
            try {
                return Crypt::decryptString($row->value);
            } catch (\Throwable) {
                return $default;
            }
        }

        return $row->value;
    }

    public static function set(string $key, mixed $value): void
    {
        $sensitive = in_array($key, static::$sensitiveKeys);
        $stored    = ($sensitive && $value) ? Crypt::encryptString((string) $value) : (string) $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'encrypted' => $sensitive]
        );
    }

    public static function setBulk(array $data): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value);
        }
    }
}
