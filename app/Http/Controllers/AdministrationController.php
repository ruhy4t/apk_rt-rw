<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Resident;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use App\Services\AreaAccess;
use App\Services\UserSessions;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdministrationController extends Controller
{
    public function index(Request $request, AreaAccess $access): View
    {
        $actor = $request->user();
        $rwAreas = RwArea::query();
        if ($actor->hasRole('rw_operator')) {
            $rwAreas->whereKey($actor->rw_area_id ?? 0);
        } elseif ($actor->hasRole('rt_operator')) {
            $rwAreas->whereHas('rtAreas', fn ($query) => $query->whereKey($actor->rt_area_id ?? 0));
        }

        return view('management.administration', [
            'chairmanRtReady' => $request->routeIs('admin.setup') && $actor->hasRole('super_admin') && Schema::hasColumn('rt_areas', 'chairman_name'),
            'chairmanRwReady' => $request->routeIs('admin.setup') && $actor->hasRole('super_admin') && Schema::hasColumn('rw_areas', 'chairman_name'),
            'rwAreas' => $rwAreas->with(['rtAreas' => function ($query) use ($actor) {
                if ($actor->hasRole('rt_operator')) {
                    $query->whereKey($actor->rt_area_id ?? 0);
                }
            }])->orderBy('name')->get(),
            'users' => $access->manageableUsers($actor)->with(['rwArea:id,name', 'rtArea:id,name'])
                ->when(! config('personal_data.encryption_enabled'), fn ($query) => $query->orderBy('name'))
                ->orderBy('id')->paginate(20),
            'unlinkedResidents' => Resident::query()
                ->whereNull('user_id')
                ->whereHas('household', fn ($query) => $query->whereIn('rt_area_id', $access->manageableRtAreas($actor)->select('id')))
                ->with('household:id,household_code,rt_area_id')
                ->when(! config('personal_data.encryption_enabled'), fn ($query) => $query->orderBy('full_name'))
                ->orderBy('id')
                ->simplePaginate(50, Resident::personalColumns(['id', 'household_id', 'full_name']), 'resident_page'),
        ]);
    }

    public function storeRw(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:rw_areas,code'],
            'name' => ['required', 'string', 'max:120'],
            'chairman_name' => ['nullable', 'string', 'max:160'],
        ]);
        $data = $this->chairmanCreationData('rw_areas', $data);

        DB::transaction(function () use ($request, $data) {
            // A stable, existing row serializes the initial empty-RW case too.
            User::query()->where('role', 'super_admin')->orderBy('id')->lockForUpdate()->firstOrFail(['id']);
            $existing = RwArea::query()->orderBy('id')->lockForUpdate()->first(['id']);
            if ($existing) {
                throw ValidationException::withMessages([
                    'rw' => 'Aplikasi dibatasi satu RW. RW tambahan tidak dapat dibuat.',
                ]);
            }

            $area = RwArea::query()->create($data);
            AuditEvent::record($request->user(), 'rw_area.created', $area);
        }, 3);

        return back()->with('status', 'Wilayah RW berhasil dibuat.');
    }

    public function storeRt(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);
        $data = $request->validate([
            'rw_area_id' => ['required', 'integer', 'exists:rw_areas,id'],
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
            'chairman_name' => ['nullable', 'string', 'max:160'],
        ]);
        $data = $this->chairmanCreationData('rt_areas', $data);

        if (RwArea::query()->count() !== 1) {
            throw ValidationException::withMessages([
                'rw_area_id' => 'Penambahan RT memerlukan tepat satu RW. Periksa pengaturan wilayah terlebih dahulu.',
            ]);
        }

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

    public function updateRt(Request $request, RtArea $rtArea): RedirectResponse
    {
        return $this->updateArea($request, $rtArea);
    }

    public function updateRw(Request $request, RwArea $rwArea): RedirectResponse
    {
        return $this->updateArea($request, $rwArea);
    }

    private function updateArea(Request $request, RtArea|RwArea $area): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);
        try {
            DB::transaction(function () use ($request, $area) {
                $locked = $area->newQuery()->whereKey($area->id)->lockForUpdate()->firstOrFail();
                $unique = Rule::unique($area->getTable(), 'code')->ignore($locked->id);
                if ($locked instanceof RtArea) {
                    $unique->where('rw_area_id', $locked->rw_area_id);
                }
                $data = $request->validate([
                    'code' => ['required', 'string', 'max:32', $unique],
                    'name' => ['required', 'string', 'max:120'],
                ], ['code.unique' => 'Kode wilayah tersebut sudah digunakan. Gunakan kode lain.']);
                $locked->update($data);
                AuditEvent::record($request->user(), $locked instanceof RtArea ? 'rt_area.updated' : 'rw_area.updated', $locked);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'Kode wilayah tersebut sudah digunakan. Muat ulang halaman dan gunakan kode lain.']);
        }

        return back()->with('status', 'Wilayah berhasil diperbarui. Hubungan data dan catatan penandatangan dokumen lama tetap dipertahankan.');
    }

    public function updateRtChairman(Request $request, RtArea $rtArea): RedirectResponse
    {
        return $this->updateChairman($request, $rtArea, 'rt_area.chairman_updated', 'Ketua RT');
    }

    public function updateRwChairman(Request $request, RwArea $rwArea): RedirectResponse
    {
        return $this->updateChairman($request, $rwArea, 'rw_area.chairman_updated', 'Ketua RW');
    }

    private function chairmanCreationData(string $table, array $data): array
    {
        $name = trim($data['chairman_name'] ?? '');
        if ($name !== '' && ! Schema::hasColumn($table, 'chairman_name')) {
            throw ValidationException::withMessages(['chairman_name' => 'Pengaturan ketua belum tersedia. Administrator perlu menerapkan pembaruan database terlebih dahulu.']);
        }
        if ($name === '') {
            unset($data['chairman_name']);
        } else {
            $data['chairman_name'] = $name;
        }

        return $data;
    }

    private function updateChairman(Request $request, RtArea|RwArea $area, string $action, string $label): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);
        if (! Schema::hasColumn($area->getTable(), 'chairman_name')) {
            throw ValidationException::withMessages(['chairman_name' => 'Pengaturan ketua belum tersedia. Administrator perlu menerapkan pembaruan database terlebih dahulu.']);
        }
        $data = $request->validate(['chairman_name' => ['required', 'string', 'max:160']]);
        DB::transaction(function () use ($request, $area, $data, $action) {
            $lockedArea = $area->newQuery()->whereKey($area->getKey())->lockForUpdate()->firstOrFail();
            $lockedArea->update(['chairman_name' => trim($data['chairman_name'])]);
            AuditEvent::record($request->user(), $action, $lockedArea);
        });

        return back()->with('status', $label.' berhasil ditetapkan. Nama pada dokumen yang sudah diterbitkan tetap menggunakan catatan saat penerbitan.');
    }

    public function storeUser(Request $request, AreaAccess $access): RedirectResponse
    {
        if (is_string($request->input('username'))) {
            $request->merge(['username' => mb_strtolower($request->input('username'))]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'username' => ['required', 'string', 'alpha_dash:ascii', 'max:80', 'unique:users,username'],
            'role' => ['required', 'in:rw_operator,rt_operator,resident'],
            'rw_area_id' => ['nullable', 'integer', 'exists:rw_areas,id'],
            'rt_area_id' => ['nullable', 'integer', 'exists:rt_areas,id'],
            'resident_id' => ['nullable', 'integer', 'exists:residents,id'],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed'],
        ]);

        if (User::query()->withUsername($data['username'])->exists()) {
            throw ValidationException::withMessages(['username' => 'Nama pengguna tersebut sudah digunakan.']);
        }

        $rwAreaId = $data['rw_area_id'] ?? null;
        $rtAreaId = $data['rt_area_id'] ?? null;
        $residentId = $data['resident_id'] ?? null;
        $actor = $request->user();

        abort_unless(
            ($actor->hasRole('super_admin'))
                || ($actor->hasRole('rw_operator') && in_array($data['role'], ['rt_operator', 'resident'], true))
                || ($actor->hasRole('rt_operator') && $data['role'] === 'resident'),
            403
        );

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

        if ($rtAreaId) {
            abort_unless($access->canManageRt($actor, (int) $rtAreaId), 403);
        }

        DB::transaction(function () use ($request, $data, $rwAreaId, $rtAreaId, $residentId, $actor, $access) {
            $resident = null;
            if ($residentId) {
                $resident = Resident::query()->with('household')->lockForUpdate()->findOrFail($residentId);
                abort_unless($resident->household && $access->canAccessHousehold($actor, $resident->household), 403);

                if ($resident->user_id) {
                    throw ValidationException::withMessages([
                        'resident_id' => 'Data warga tersebut sudah memiliki akun.',
                    ]);
                }
            }

            $user = User::query()->create([
                'name' => $data['name'],
                'username' => mb_strtolower($data['username']),
                'role' => $data['role'],
                'is_active' => true,
                'rw_area_id' => $rwAreaId,
                'rt_area_id' => $rtAreaId,
                'password' => $data['password'],
                'must_change_password' => true,
            ]);

            if ($resident) {
                $resident->update(['user_id' => $user->id]);
            }

            AuditEvent::record($request->user(), 'user.created', $user);
        });

        return back()->with('status', 'Akun berhasil dibuat. Berikan nama pengguna dan kata sandi kepada pemilik akun melalui kanal yang aman.');
    }

    public function setUserStatus(Request $request, User $user, AreaAccess $access, UserSessions $sessions): RedirectResponse
    {
        abort_unless($access->manageableUsers($request->user())->whereKey($user->id)->exists(), 403);

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $user, $data, $sessions) {
            $user->update(['is_active' => (bool) $data['is_active']]);
            if (! $user->is_active) {
                $sessions->revoke($user);
            }
            AuditEvent::record($request->user(), $user->is_active ? 'user.activated' : 'user.deactivated', $user);
        });

        return back()->with('status', 'Status akun berhasil diperbarui.');
    }

    public function updateUser(Request $request, User $user, AreaAccess $access): RedirectResponse
    {
        abort_unless($access->manageableUsers($request->user())->whereKey($user->id)->exists(), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:160']]);
        DB::transaction(function () use ($request, $user, $data) {
            $user->update($data);
            AuditEvent::record($request->user(), 'user.updated', $user);
        });

        return back()->with('status', 'Nama akun berhasil diperbarui.');
    }

    public function resetPassword(Request $request, User $user, AreaAccess $access, UserSessions $sessions): RedirectResponse
    {
        abort_unless($access->manageableUsers($request->user())->whereKey($user->id)->exists(), 403);
        $data = $request->validate(['password' => ['required', 'string', 'min:12', 'max:255', 'confirmed']]);

        DB::transaction(function () use ($request, $user, $data, $sessions) {
            $user->forceFill(['password' => $data['password'], 'must_change_password' => true, 'remember_token' => null])->save();
            $sessions->revoke($user);
            AuditEvent::record($request->user(), 'user.password_reset', $user);
        });

        return back()->with('status', 'Kata sandi direset. Sampaikan kata sandi sementara melalui kanal yang aman.');
    }
}
