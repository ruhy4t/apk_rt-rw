<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityAsset extends Model
{
    protected $fillable = [
        'rt_area_id',
        'created_by',
        'name',
        'category',
        'quantity',
        'condition',
        'public_summary',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'public_summary' => 'boolean',
        ];
    }

    public function rtArea(): BelongsTo
    {
        return $this->belongsTo(RtArea::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(AssetLoan::class, 'asset_id');
    }
}
