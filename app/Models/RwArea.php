<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RwArea extends Model
{
    protected $fillable = ['code', 'name', 'chairman_name', 'village_name', 'district_name', 'city_name'];

    protected $hidden = ['chairman_name'];

    protected function casts(): array
    {
        return ['chairman_name' => 'encrypted'];
    }

    public function rtAreas(): HasMany
    {
        return $this->hasMany(RtArea::class);
    }
}
