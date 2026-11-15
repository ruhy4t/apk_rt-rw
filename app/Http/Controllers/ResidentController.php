<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Household;
use App\Models\Resident;
use App\Services\AreaAccess;
use App\Services\IdentityDigest;
use App\Services\ResidentInputRules;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResidentController extends Controller
{
    public function index(Request $request, AreaAccess $access): View
    {
        $areas = $access->manageableRtAreas($request->user())->orderBy('name')->get();
        $households = Household::query()
            ->whereIn('rt_area_id', $areas->modelKeys())
            ->with(['rtArea:id,name,code', 'residents' => fn ($query) => $query->select(Resident::personalColumns(['id', 'household_id', 'full_name', 'relationship', 'phone']))])
            ->orderBy('household_code')
            ->paginate(25);

        return view('management.residents', compact('areas', 'households'));
    }

    public function storeHousehold(Request $request, AreaAccess $access, IdentityDigest $digest): RedirectResponse
    {
        $data = $request->validate([
            'rt_area_id' => ['required', 'integer', 'exists:rt_areas,id'],
            'household_code' => ['required', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:2000'],
            'family_card_number' => ['required', 'string', 'regex:/^[0-9]{16}$/D'],
        ]);
        abort_unless($access->canManageRt($request->user(), (int) $data['rt_area_id']), 403);

        $residentData = $this->validateResident($request, $digest);
        $cardDigest = $digest->make('kk', $data['family_card_number']);
        if (Household::query()->where('family_card_digest', $cardDigest)->exists()) {
            throw ValidationException::withMessages(['family_card_number' => 'Nomor KK sudah terdaftar.']);
        }

        try {
            DB::transaction(function () use ($request, $data, $residentData, $cardDigest) {
                $household = Household::query()->create($data + ['family_card_digest' => $cardDigest]);
                $resident = $household->residents()->create($residentData + ['relationship' => 'Kepala Keluarga']);
                AuditEvent::record($request->user(), 'household.created', $household);
                AuditEvent::record($request->user(), 'resident.created', $resident);
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (Household::query()->where('family_card_digest', $cardDigest)->exists()) {
                throw ValidationException::withMessages(['family_card_number' => 'Nomor KK sudah terdaftar.']);
            }
            if (Resident::query()->where('national_id_digest', $residentData['national_id_digest'])->exists()) {
                throw ValidationException::withMessages(['national_id' => 'NIK sudah terdaftar.']);
            }
            throw $exception;
        }

        return back()->with('status', 'Rumah tangga berhasil ditambahkan.');
    }

    public function storeResident(Request $request, Household $household, AreaAccess $access, IdentityDigest $digest): RedirectResponse
    {
        abort_unless($access->canManageRt($request->user(), $household->rt_area_id), 403);

        $data = $this->validateResident($request, $digest);
        $data += $request->validate(['relationship' => ['required', 'in:Kepala Keluarga,Istri,Suami,Anak,Famili Lain']]);

        try {
            DB::transaction(function () use ($request, $household, $data) {
                Household::query()->whereKey($household->id)->lockForUpdate()->firstOrFail();
                if ($data['relationship'] === 'Kepala Keluarga' && $household->residents()->heads()->exists()) {
                    throw ValidationException::withMessages(['relationship' => 'Kepala keluarga sudah ditetapkan. Gunakan Ganti kepala keluarga untuk menggantinya.']);
                }
                $resident = $household->residents()->create($data);
                AuditEvent::record($request->user(), 'resident.created', $resident);
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (Resident::query()->where('national_id_digest', $data['national_id_digest'])->exists()) {
                throw ValidationException::withMessages(['national_id' => 'NIK sudah terdaftar.']);
            }
            throw $exception;
        }

        return back()->with('status', 'Data warga berhasil ditambahkan.');
    }

    public function createResident(Request $request, Household $household, AreaAccess $access): View
    {
        abort_unless($access->canManageRt($request->user(), $household->rt_area_id), 403);

        $hasHead = $household->residents()->heads()->exists();

        return view('management.resident-create', compact('household', 'hasHead'));
    }

    public function updateHead(Request $request, Household $household, AreaAccess $access): RedirectResponse
    {
        abort_unless($access->canManageRt($request->user(), $household->rt_area_id), 403);
        $data = $request->validate([
            'new_head_id' => ['required', 'integer', 'min:1'],
            'current_head_id' => ['present', 'nullable', 'integer', 'min:1'],
            'previous_relationship' => ['nullable', 'in:Kepala Keluarga Sebelumnya,Istri,Suami,Anak,Famili Lain'],
        ]);

        DB::transaction(function () use ($request, $household, $data) {
            Household::query()->whereKey($household->id)->lockForUpdate()->firstOrFail();
            $heads = $household->residents()->heads()->lockForUpdate()->get();
            if ($heads->count() > 1) {
                throw ValidationException::withMessages(['head' => 'Terdapat lebih dari satu kepala keluarga. Periksa data rumah tangga ini sebelum melanjutkan.']);
            }
            $current = $heads->first();
            if ((int) $current?->id !== (int) ($data['current_head_id'] ?? 0)) {
                throw ValidationException::withMessages(['head' => 'Kepala keluarga sudah berubah. Muat ulang halaman sebelum melanjutkan.']);
            }
            $next = $household->residents()->whereKey($data['new_head_id'])->lockForUpdate()->first();
            if (! $next || $next->is($current)) {
                throw ValidationException::withMessages(['new_head_id' => 'Pilih anggota lain dari rumah tangga yang sama.']);
            }
            if ($current) {
                if (empty($data['previous_relationship'])) {
                    throw ValidationException::withMessages(['previous_relationship' => 'Tentukan hubungan kepala keluarga sebelumnya.']);
                }
                $current->update(['relationship' => $data['previous_relationship']]);
                AuditEvent::record($request->user(), 'resident.head_replaced', $current);
            }
            $next->update(['relationship' => 'Kepala Keluarga']);
            AuditEvent::record($request->user(), 'resident.head_assigned', $next);
            AuditEvent::record($request->user(), 'household.head_updated', $household);
        });

        return redirect()->route('residents.index')->with('status', 'Kepala keluarga berhasil diperbarui. Periksa kembali hubungan anggota keluarga lainnya.');
    }

    public function editResident(Request $request, Resident $resident, AreaAccess $access): View
    {
        abort_unless($access->canManageRt($request->user(), $resident->household->rt_area_id), 403);

        return view('management.resident-edit', compact('resident'));
    }

    public function updateDetails(Request $request, Resident $resident, AreaAccess $access, IdentityDigest $digest): RedirectResponse
    {
        abort_unless($access->canManageRt($request->user(), $resident->household->rt_area_id), 403);
        $data = $this->validateResident($request, $digest, $resident);
        if ($resident->relationship !== 'Kepala Keluarga') {
            $data += $request->validate(['relationship' => ['required', 'in:Istri,Suami,Anak,Famili Lain,Kepala Keluarga Sebelumnya']]);
        }

        try {
            DB::transaction(function () use ($request, $resident, $data) {
                Household::query()->lockForUpdate()->findOrFail($resident->household_id);
                $resident->refresh();
                $this->ensureHeadUnchanged($resident, $data);
                $resident->update($data);
                AuditEvent::record($request->user(), 'resident.updated', $resident);
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (isset($data['national_id_digest']) && Resident::query()
                ->where('national_id_digest', $data['national_id_digest'])->whereKeyNot($resident->id)->exists()) {
                throw ValidationException::withMessages(['national_id' => 'NIK sudah terdaftar.']);
            }
            throw $exception;
        }

        return redirect()->route('residents.index')->with('status', 'Data warga berhasil diperbarui.');
    }

    public function updateFamilyCard(Request $request, Household $household, AreaAccess $access, IdentityDigest $digest): RedirectResponse
    {
        abort_unless($access->canManageRt($request->user(), $household->rt_area_id), 403);
        $data = $request->validate(['family_card_number' => ['required', 'string', 'regex:/^[0-9]{16}$/D']]);
        $data['family_card_digest'] = $digest->make('kk', $data['family_card_number']);
        if (Household::query()->where('family_card_digest', $data['family_card_digest'])->whereKeyNot($household->id)->exists()) {
            throw ValidationException::withMessages(['family_card_number' => 'Nomor KK sudah terdaftar.']);
        }
        try {
            DB::transaction(function () use ($request, $household, $data) {
                $household->update($data);
                AuditEvent::record($request->user(), 'household.identity_updated', $household);
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (Household::query()->where('family_card_digest', $data['family_card_digest'])->whereKeyNot($household->id)->exists()) {
                throw ValidationException::withMessages(['family_card_number' => 'Nomor KK sudah terdaftar.']);
            }
            throw $exception;
        }

        return back()->with('status', 'Nomor KK berhasil diperbarui.');
    }

    public function updateResident(Request $request, Resident $resident, AreaAccess $access): RedirectResponse
    {
        abort_unless($access->canManageRt($request->user(), $resident->household->rt_area_id), 403);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'relationship' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:32'],
            'clear_phone' => ['nullable', 'boolean'],
        ]);

        $data = $this->preparePhoneUpdate($data, $resident);

        DB::transaction(function () use ($request, $resident, $data) {
            Household::query()->lockForUpdate()->findOrFail($resident->household_id);
            $resident->refresh();
            $this->ensureHeadUnchanged($resident, $data);
            $resident->update($data);
            AuditEvent::record($request->user(), 'resident.updated', $resident);
        });

        return back()->with('status', 'Data warga berhasil diperbarui.');
    }

    private function ensureHeadUnchanged(Resident $resident, array $data): void
    {
        if (array_key_exists('relationship', $data)
            && ($resident->relationship === 'Kepala Keluarga') !== ($data['relationship'] === 'Kepala Keluarga')) {
            throw ValidationException::withMessages(['relationship' => 'Kepala keluarga tidak dapat diubah melalui formulir ini.']);
        }
    }

    private function validateResident(Request $request, IdentityDigest $digest, ?Resident $resident = null): array
    {
        $data = $request->validate(ResidentInputRules::rules($resident));
        if (! empty($data['national_id'])) {
            $data['national_id_digest'] = $digest->make('nik', $data['national_id']);
            $existing = Resident::query()->where('national_id_digest', $data['national_id_digest']);
            if ($resident) {
                $existing->whereKeyNot($resident->id);
            }
            if ($existing->exists()) {
                throw ValidationException::withMessages(['national_id' => 'NIK sudah terdaftar.']);
            }
        } else {
            unset($data['national_id']);
        }

        return $this->preparePhoneUpdate($data, $resident);
    }

    private function preparePhoneUpdate(array $data, ?Resident $resident): array
    {
        $clear = ! empty($data['clear_phone']);
        unset($data['clear_phone']);
        if ($clear && ! empty($data['phone'])) {
            throw ValidationException::withMessages(['phone' => 'Isi nomor baru atau pilih hapus nomor, bukan keduanya.']);
        }
        if ($resident && $clear) {
            $data['phone'] = null;
        } elseif ($resident && empty($data['phone'])) {
            unset($data['phone']);
        }

        return $data;
    }
}
