<?php

namespace App\Models;

use App\Models\Concerns\HasSignatory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Announcement extends Model
{
    use HasSignatory;

    protected $fillable = [
        'rt_area_id',
        'created_by',
        'title',
        'body',
        'audience',
        'is_published',
        'published_at',
        'signatory_data',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function rtArea(): BelongsTo
    {
        return $this->belongsTo(RtArea::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photo(): MorphOne
    {
        return $this->morphOne(StoredFile::class, 'fileable');
    }
}
