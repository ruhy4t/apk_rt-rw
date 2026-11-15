<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RtArea extends Model
{
    protected $fillable = ['rw_area_id', 'code', 'name', 'chairman_name'];

    protected $hidden = ['chairman_name'];

    protected function casts(): array
    {
        return ['chairman_name' => 'encrypted'];
    }

    public function rwArea(): BelongsTo
    {
        return $this->belongsTo(RwArea::class);
    }

    public function households(): HasMany
    {
        return $this->hasMany(Household::class);
    }
}
