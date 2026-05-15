<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TalosMedia extends Model
{
    protected $table = 'talos_media';

    protected $fillable = [
        'name', 'alternative_text', 'caption', 'mime_type',
        'ext', 'size', 'url', 'path', 'width', 'height',
        'formats', 'hash', 'uploaded_by', 'folder',
    ];

    protected $casts    = ['formats' => 'array'];
    protected $appends  = ['url'];
    protected $hidden   = [];

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    public function uploader()
    {
        return $this->belongsTo(TalosUser::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
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
