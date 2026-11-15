<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedPersonalData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    use HasEncryptedPersonalData;

    protected const PERSONAL_FIELDS = [
        'address' => null,
    ];

    protected $fillable = ['rt_area_id', 'household_code', 'address', 'family_card_number', 'family_card_digest'];

    protected $hidden = ['family_card_number', 'family_card_digest'];

    protected function casts(): array
    {
        return ['family_card_number' => 'encrypted', 'private_data' => 'encrypted:array'];
    }

    public function rtArea(): BelongsTo
    {
        return $this->belongsTo(RtArea::class);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }
}
