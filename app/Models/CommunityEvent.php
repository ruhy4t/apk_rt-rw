<?php

namespace App\Models;

use App\Models\Concerns\HasSignatory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CommunityEvent extends Model
{
    use HasSignatory;

    protected $fillable = [
        'rt_area_id',
        'created_by',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'audience',
        'is_published',
        'signatory_data',
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
