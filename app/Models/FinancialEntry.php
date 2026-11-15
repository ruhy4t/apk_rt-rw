<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedPersonalData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialEntry extends Model
{
    use HasEncryptedPersonalData;

    protected const PERSONAL_FIELDS = [
        'description' => null,
    ];

    protected $fillable = [
        'rt_area_id',
        'recorded_by',
        'direction',
        'category',
        'amount',
        'description',
        'occurred_on',
        'public_summary',
    ];

    protected function casts(): array
    {
        return [
            'private_data' => 'encrypted:array',
            'amount' => 'integer',
            'occurred_on' => 'date',
            'public_summary' => 'boolean',
        ];
    }

    public function rtArea(): BelongsTo
    {
        return $this->belongsTo(RtArea::class);
    }
}
