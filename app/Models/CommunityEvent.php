<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CommunityEvent extends Model
{
    protected $fillable = [
        'rt_area_id',
        'created_by',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'audience',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    public function rtArea(): BelongsTo
    {
        return $this->belongsTo(RtArea::class);
    }

    public function photo(): MorphOne
    {
        return $this->morphOne(StoredFile::class, 'fileable');
    }
}
