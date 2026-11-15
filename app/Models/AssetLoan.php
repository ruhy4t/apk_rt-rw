<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetLoan extends Model
{
    protected $fillable = [
        'asset_id',
        'household_id',
        'requested_by',
        'approved_by',
        'quantity',
        'purpose',
        'starts_at',
        'due_at',
        'returned_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'starts_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(CommunityAsset::class, 'asset_id');
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
