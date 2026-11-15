<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContentVisibility
{
    public function __construct(private AreaAccess $access) {}

    public function canManage(?User $user, Model $content): bool
    {
        return $this->usable($user) && $user->hasRole('super_admin', 'rw_operator', 'rt_operator')
            && ($content->rt_area_id === null ? $user->hasRole('super_admin') : $this->access->canManageRt($user, $content->rt_area_id));
    }

    public function canRead(?User $user, Model $content): bool
    {
        if ($this->canManage($user, $content)) {
            return true;
        }
        if (! $content->is_published || ($content instanceof Announcement && $content->published_at?->isFuture())) {
            return false;
        }
        if ($content->audience === 'public') {
            return true;
        }
        if ($content->audience !== 'residents' || ! $this->usable($user)) {
            return false;
        }
        if ($content->rt_area_id === null) {
            return $user->hasRole('super_admin', 'rw_operator', 'rt_operator', 'resident');
        }

        return $user->hasRole('resident') && $user->resident()->whereHas('household', fn ($query) => $query->where('rt_area_id', $content->rt_area_id))->exists();
    }

    public function visibleGallery(Builder $query, ?User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            $query->where(fn (Builder $public) => $public->where('audience', 'public')->where('is_published', true));
            if (! $this->usable($user)) {
                return;
            }
            if ($user->hasRole('super_admin')) {
                $query->orWhereRaw('1 = 1');
            } elseif ($user->hasRole('rw_operator', 'rt_operator')) {
                $ids = $this->access->manageableRtAreas($user)->pluck('id');
                $query->orWhereIn('rt_area_id', $ids)->orWhere(fn (Builder $rw) => $rw->whereNull('rt_area_id')->where('audience', 'residents')->where('is_published', true));
            } elseif ($user->hasRole('resident')) {
                $rtId = $user->resident?->household?->rt_area_id;
                $query->orWhere(function (Builder $private) use ($rtId) {
                    $private->where('audience', 'residents')->where('is_published', true)
                        ->where(fn (Builder $area) => $area->whereNull('rt_area_id')->when($rtId, fn (Builder $area) => $area->orWhere('rt_area_id', $rtId)));
                });
            }
        });
    }

    private function usable(?User $user): bool
    {
        return $user && $user->is_active && ! $user->must_change_password;
    }
}
