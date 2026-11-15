<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Resident;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdministrationController extends Controller
{
    public function index(): View
    {
        return view('management.administration', [
            'rwAreas' => RwArea::query()->with('rtAreas')->orderBy('name')->get(),
            'users' => User::query()->with(['rwArea:id,name', 'rtArea:id,name'])->orderBy('name')->paginate(20),
            'unlinkedResidents' => Resident::query()
                ->whereNull('user_id')
                ->with('household:id,household_code,rt_area_id')
                ->orderBy('full_name')
                ->limit(100)
                ->get(['id', 'household_id', 'full_name']),
        ]);
    }

    public function storeRw(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:rw_areas,code'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        $area = RwArea::query()->create($data);
        AuditEvent::record($request->user(), 'rw_area.created', $area);

        return back()->with('status', 'Wilayah RW berhasil dibuat.');
    }

    public function storeRt(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rw_area_id' => ['required', 'integer', 'exists:rw_areas,id'],
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        $exists = RtArea::query()
            ->where('rw_area_id', $data['rw_area_id'])
            ->where('code', $data['code'])
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'code' => 'Kode RT tersebut sudah digunakan pada RW yang dipilih.',
            ]);
        }

        $area = RtArea::query()->create($data);
        AuditEvent::record($request->user(), 'rt_area.created', $area);

        return back()->with('status', 'Wilayah RT berhasil dibuat.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'username' => ['required', 'string', 'alpha_dash:ascii', 'max:80', 'unique:users,username'],
            'role' => ['required', 'in:rw_operator,rt_operator,resident'],
            'rw_area_id' => ['nullable', 'integer', 'exists:rw_areas,id'],
            'rt_area_id' => ['nullable', 'integer', 'exists:rt_areas,id'],
            'resident_id' => ['nullable', 'integer', 'exists:residents,id'],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed'],
        ]);

        $rwAreaId = $data['rw_area_id'] ?? null;
        $rtAreaId = $data['rt_area_id'] ?? null;
        $residentId = $data['resident_id'] ?? null;

        if ($data['role'] === 'rw_operator' && (! $rwAreaId || $rtAreaId || $residentId)) {
            throw ValidationException::withMessages([
                'role' => 'Petugas RW harus ditetapkan tepat pada satu wilayah RW.',
            ]);
        }

        if ($data['role'] === 'rt_operator' && (! $rtAreaId || $rwAreaId || $residentId)) {
            throw ValidationException::withMessages([
                'role' => 'Petugas RT harus ditetapkan tepat pada satu wilayah RT.',
            ]);
        }

        if ($data['role'] === 'resident' && (! $residentId || $rwAreaId || $rtAreaId)) {
            throw ValidationException::withMessages([
                'role' => 'Akun warga harus terhubung ke satu data warga dan tanpa cakupan petugas.',
            ]);
        }

        DB::transaction(function () use ($request, $data, $rwAreaId, $rtAreaId, $residentId) {
            $user = User::query()->create([
                'name' => $data['name'],
                'username' => mb_strtolower($data['username']),
                'role' => $data['role'],
                'is_active' => true,
                'rw_area_id' => $rwAreaId,
                'rt_area_id' => $rtAreaId,
                'password' => $data['password'],
            ]);

            if ($residentId) {
                $resident = Resident::query()->lockForUpdate()->findOrFail($residentId);

                if ($resident->user_id) {
                    throw ValidationException::withMessages([
                        'resident_id' => 'Data warga tersebut sudah memiliki akun.',
                    ]);
                }

                $resident->update(['user_id' => $user->id]);
            }

            AuditEvent::record($request->user(), 'user.created', $user);
        });

        return back()->with('status', 'Akun berhasil dibuat. Berikan nama pengguna dan kata sandi kepada pemilik akun melalui kanal yang aman.');
    }

    public function setUserStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'Administrator tidak dapat menonaktifkan akunnya sendiri.');

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update(['is_active' => (bool) $data['is_active']]);
        AuditEvent::record(
            $request->user(),
            $user->is_active ? 'user.activated' : 'user.deactivated',
            $user
        );

        return back()->with('status', 'Status akun berhasil diperbarui.');
    }
}
