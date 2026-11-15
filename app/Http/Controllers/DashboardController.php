<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $households = Household::query();
        $residents = Resident::query();

        if ($user->hasRole('rt_operator')) {
            abort_unless($user->rt_area_id, 403);
            $households->where('rt_area_id', $user->rt_area_id);
            $residents->whereHas('household', fn ($query) => $query->where('rt_area_id', $user->rt_area_id));
        } elseif ($user->hasRole('rw_operator')) {
            abort_unless($user->rw_area_id, 403);
            $households->whereHas('rtArea', fn ($query) => $query->where('rw_area_id', $user->rw_area_id));
            $residents->whereHas('household.rtArea', fn ($query) => $query->where('rw_area_id', $user->rw_area_id));
        } elseif ($user->hasRole('resident')) {
            $resident = $user->resident()->with('household.rtArea')->first();
            $household = $resident?->household;

            return view('dashboard', [
                'roleLabel' => 'Warga',
                'stats' => [
                    'Rumah tangga saya' => $household ? 1 : 0,
                    'Pengumuman warga' => DB::table('announcements')
                        ->where('audience', 'residents')
                        ->where('is_published', true)
                        ->where(function ($query) use ($household) {
                            $query->whereNull('rt_area_id');

                            if ($household) {
                                $query->orWhere('rt_area_id', $household->rt_area_id);
                            }
                        })
                        ->count(),
                ],
                'householdCode' => $household?->household_code,
            ]);
        } elseif (! $user->hasRole('super_admin')) {
            abort(403);
        }

        return view('dashboard', [
            'roleLabel' => match ($user->role) {
                'super_admin' => 'Administrator',
                'rw_operator' => 'Petugas RW',
                default => 'Petugas RT',
            },
            'stats' => [
                'Rumah tangga' => $households->count(),
                'Warga terdata' => $residents->count(),
            ],
            'householdCode' => null,
        ]);
    }
}
