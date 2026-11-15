<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedPersonalData;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'is_active', 'rw_area_id', 'rt_area_id', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasEncryptedPersonalData;

    protected const PERSONAL_FIELDS = [
        'name' => '[protected]',
        'username' => '[protected]',
        'email' => null,
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'private_data' => 'encrypted:array',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function scopeWithUsername($query, string $username)
    {
        return $query->whereIn('username', [$username, static::personalLookup('username', $username)]);
    }

    public function rwArea(): BelongsTo
    {
        return $this->belongsTo(RwArea::class);
    }

    public function rtArea(): BelongsTo
    {
        return $this->belongsTo(RtArea::class);
    }

    public function resident(): HasOne
    {
        return $this->hasOne(Resident::class);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
