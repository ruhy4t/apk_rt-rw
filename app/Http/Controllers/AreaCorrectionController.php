<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AuditEvent;
use App\Models\CommunityEvent;
use App\Models\RtArea;
use App\Models\RwArea;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AreaCorrectionController extends Controller
{
    public function convertRwToRt(Request $request, RwArea $rwArea): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);
        $data = $request->validate([
            'target_rw_area_id' => ['required', 'integer', 'exists:rw_areas,id'],
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
            'confirm_conversion' => ['required', 'accepted'],
        ], ['confirm_conversion.accepted' => 'Konfirmasi koreksi wilayah sebelum melanjutkan.']);

        if ((int) $data['target_rw_area_id'] === $rwArea->id) {
            $this->reject('Pilih RW tujuan yang berbeda dari wilayah yang dikoreksi.');
        }
        try {
            DB::transaction(function () use ($request, $rwArea, $data) {
                $areas = RwArea::query()->whereIn('id', [$rwArea->id, (int) $data['target_rw_area_id']])
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $source = $areas->get($rwArea->id);
                $target = $areas->get((int) $data['target_rw_area_id']);
                if (! $source || ! $target) {
                    $this->reject('Wilayah berubah sejak formulir dibuka. Muat ulang halaman, lalu periksa kembali.');
                }
                if ($source->rtAreas()->exists() || User::query()->where('rw_area_id', $source->id)->exists()) {
                    $this->reject('RW asal masih memiliki RT atau akun yang terhubung. Koreksi otomatis tidak dapat dilakukan.');
                }
                foreach (['village_name', 'district_name', 'city_name'] as $field) {
                    if (trim((string) $source->getRawOriginal($field)) !== '') {
                        $this->reject('RW asal sudah memiliki identitas administrasi. Periksa data wilayah sebelum melakukan koreksi.');
                    }
                }
                $this->ensureNoIssuedRecord($source->id);
                $request->validate([
                    'code' => [Rule::unique('rt_areas', 'code')->where('rw_area_id', $target->id)],
                ], ['code.unique' => 'Kode RT tersebut sudah digunakan pada RW tujuan.']);

                $values = ['rw_area_id' => $target->id, 'code' => $data['code'], 'name' => $data['name']];
                if (Schema::hasColumn('rw_areas', 'chairman_name') && $source->chairman_name !== null) {
                    if (! Schema::hasColumn('rt_areas', 'chairman_name')) {
                        $this->reject('Pembaruan struktur Ketua RT belum lengkap. Nama ketua pada RW asal tetap dipertahankan.');
                    }
                    $values['chairman_name'] = $source->chairman_name;
                }
                $rt = RtArea::query()->create($values);
                AuditEvent::record($request->user(), 'rt_area.created_from_rw', $rt);
                AuditEvent::record($request->user(), 'rw_area.corrected_to_rt', $source);
                $source->delete();
            });
        } catch (DecryptException) {
            $this->reject('Data ketua belum dapat diperiksa. Koreksi wilayah tidak disimpan.');
        } catch (QueryException) {
            $this->reject('Koreksi belum disimpan karena ada konflik atau perubahan data. Muat ulang halaman dan periksa keterkaitan wilayah.');
        }

        return redirect()->route('admin.setup')->with('status', 'Wilayah yang salah dicatat sebagai RW berhasil dikoreksi menjadi RT pada RW tujuan.');
    }

    private function ensureNoIssuedRecord(int $rwId): void
    {
        foreach ([Announcement::class, CommunityEvent::class] as $modelClass) {
            $model = new $modelClass;
            if (! Schema::hasColumn($model->getTable(), 'signatory_data')) {
                continue;
            }
            try {
                $modelClass::query()->whereNull('rt_area_id')->whereNotNull('signatory_data')
                    ->select(['id', 'signatory_data'])->chunkById(100, function ($records) use ($rwId) {
                        foreach ($records as $record) {
                            $snapshot = $record->signatory_data;
                            if (! is_array($snapshot) || ! isset($snapshot['area_type'], $snapshot['area_id'])) {
                                $this->reject('Catatan penandatangan lama perlu diperiksa sebelum koreksi wilayah.');
                            }
                            if (strtolower((string) $snapshot['area_type']) === 'rw' && (int) $snapshot['area_id'] === $rwId) {
                                $this->reject('RW asal masih tercatat sebagai wilayah penandatangan dokumen lama. Koreksi otomatis tidak dapat dilakukan.');
                            }
                        }
                    });
            } catch (DecryptException) {
                $this->reject('Catatan penandatangan lama belum dapat diperiksa. Koreksi wilayah tidak disimpan.');
            }
        }
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['conversion' => $message]);
    }
}
