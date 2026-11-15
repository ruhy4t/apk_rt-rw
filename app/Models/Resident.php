<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resident extends Model
{
    protected $fillable = [
        'household_id', 'user_id', 'full_name', 'relationship', 'phone',
        'national_id', 'national_id_digest', 'birthplace', 'birth_date',
        'gender', 'religion', 'marital_status', 'education', 'occupation', 'residency_status',
    ];

    protected $hidden = ['national_id', 'national_id_digest', 'religion'];

    protected function casts(): array
    {
        return [
            'national_id' => 'encrypted',
            'religion' => 'encrypted',
            'birth_date' => 'date',
        ];
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
