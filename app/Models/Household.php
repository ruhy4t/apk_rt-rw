<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    protected $fillable = ['rt_area_id', 'household_code', 'address'];

    public function rtArea(): BelongsTo
    {
        return $this->belongsTo(RtArea::class);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }
}
