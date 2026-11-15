<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedPersonalData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resident extends Model
{
    use HasEncryptedPersonalData;

    protected const PERSONAL_FIELDS = [
        'full_name' => '[protected]',
        'relationship' => null,
        'phone' => null,
        'birthplace' => null,
        'birth_date' => null,
        'gender' => null,
        'marital_status' => null,
        'education' => null,
        'occupation' => null,
        'residency_status' => null,
    ];

    protected $fillable = [
        'household_id', 'user_id', 'full_name', 'relationship', 'phone',
        'national_id', 'national_id_digest', 'birthplace', 'birth_date',
        'gender', 'religion', 'marital_status', 'education', 'occupation', 'residency_status',
    ];

    protected $hidden = ['national_id', 'national_id_digest', 'religion'];

    protected function casts(): array
    {
        return [
            'private_data' => 'encrypted:array',
            'national_id' => 'encrypted',
            'religion' => 'encrypted',
            'birth_date' => 'date',
        ];
    }

    public function scopeHeads($query)
    {
        return $query->whereIn('relationship', ['Kepala Keluarga', static::personalLookup('relationship', 'Kepala Keluarga')]);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
