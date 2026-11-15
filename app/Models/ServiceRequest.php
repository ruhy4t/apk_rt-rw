<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ServiceRequest extends Model
{
    protected $fillable = [
        'household_id',
        'requester_id',
        'assigned_to',
        'category',
        'subject',
        'description',
        'response',
        'status',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(StoredFile::class, 'fileable');
    }
}
