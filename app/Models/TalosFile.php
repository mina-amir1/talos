<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TalosFile extends Model
{
    protected $table = 'talos_files';

    protected $fillable = [
        'original_name', 'mime_type', 'ext', 'size', 'path', 'disk', 'hash',
    ];

    protected $appends = ['url'];

    /**
     * Unlike media (public by design), `file` uploads are private — no direct public
     * link. This points at the authenticated download endpoint instead of the raw
     * disk URL, so a caller still needs a valid API token / admin session to fetch it.
     */
    public function getUrlAttribute(): string
    {
        return url("/api/files/{$this->id}/download");
    }

    public function humanSize(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
