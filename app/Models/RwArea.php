<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RwArea extends Model
{
    protected $fillable = ['code', 'name'];

    public function rtAreas(): HasMany
    {
        return $this->hasMany(RtArea::class);
    }
}
