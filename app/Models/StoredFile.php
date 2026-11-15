<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StoredFile extends Model
{
    protected $fillable = [
        'uploaded_by',
        'disk',
        'object_key',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer'];
    }

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
