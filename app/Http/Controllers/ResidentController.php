<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Household;
use App\Models\Resident;
use App\Services\AreaAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function index(Request $request, AreaAccess $access): View
    {
        $areas = $access->manageableRtAreas($request->user())->orderBy('name')->get();
        $households = Household::query()
            ->whereIn('rt_area_id', $areas->modelKeys())
            ->with(['rtArea:id,name,code', 'residents:id,household_id,full_name,relationship,phone'])
            ->orderBy('household_code')
            ->paginate(25);

        return view('management.residents', compact('areas', 'households'));
    }

    public function storeHousehold(Request $request, AreaAccess $access): RedirectResponse
    {
        $data = $request->validate([
            'rt_area_id' => ['required', 'integer', 'exists:rt_areas,id'],
            'household_code' => ['required', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless($access->canManageRt($request->user(), (int) $data['rt_area_id']), 403);

        $household = Household::query()->create($data);
        AuditEvent::record($request->user(), 'household.created', $household);

        return back()->with('status', 'Rumah tangga berhasil ditambahkan.');
    }

    public function storeResident(Request $request, Household $household, AreaAccess $access): RedirectResponse
    {
        abort_unless($access->canManageRt($request->user(), $household->rt_area_id), 403);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'relationship' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $resident = $household->residents()->create($data);
        AuditEvent::record($request->user(), 'resident.created', $resident);

        return back()->with('status', 'Data warga berhasil ditambahkan.');
    }

    public function updateResident(Request $request, Resident $resident, AreaAccess $access): RedirectResponse
    {
        abort_unless($access->canManageRt($request->user(), $resident->household->rt_area_id), 403);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'relationship' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $resident->update($data);
        AuditEvent::record($request->user(), 'resident.updated', $resident);

        return back()->with('status', 'Data warga berhasil diperbarui.');
    }
}
