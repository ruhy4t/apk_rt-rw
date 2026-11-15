<?php

namespace App\Services;

use App\Models\Household;
use App\Models\RtArea;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AreaAccess
{
    public function manageableRtAreas(User $user): Builder
    {
        $query = RtArea::query();

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if ($user->hasRole('rt_operator')) {
            return $query->whereKey($user->rt_area_id ?? 0);
        }

        if ($user->hasRole('rw_operator')) {
            return $query->where('rw_area_id', $user->rw_area_id ?? 0);
        }

        return $query->whereRaw('1 = 0');
    }

    public function canManageRt(User $user, int $rtAreaId): bool
    {
        return $this->manageableRtAreas($user)->whereKey($rtAreaId)->exists();
    }

    public function canAccessHousehold(User $user, Household $household): bool
    {
        if ($user->hasRole('resident')) {
            return $user->resident()->where('household_id', $household->id)->exists();
        }

        return $this->canManageRt($user, $household->rt_area_id);
    }
}
