<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedPersonalData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contribution extends Model
{
    use HasEncryptedPersonalData;

    protected const PERSONAL_FIELDS = [
        'note' => null,
    ];

    protected $fillable = [
        'household_id',
        'recorded_by',
        'amount',
        'period',
        'paid_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'private_data' => 'encrypted:array',
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
