<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedPersonalData;
use App\Models\Concerns\HasSignatory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ServiceRequest extends Model
{
    use HasEncryptedPersonalData;
    use HasSignatory;

    protected const PERSONAL_FIELDS = [
        'subject' => '[protected]',
        'description' => '[protected]',
        'response' => null,
    ];

    protected $fillable = [
        'household_id',
        'requester_id',
        'assigned_to',
        'category',
        'subject',
        'description',
        'response',
        'status',
        'signatory_data',
    ];

    protected function casts(): array
    {
        return ['private_data' => 'encrypted:array'];
    }

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
