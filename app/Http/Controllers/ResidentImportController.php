<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Household;
use App\Models\RwArea;
use App\Services\AreaAccess;
use App\Services\ResidentImport\ResidentImportValidator;
use App\Services\ResidentImport\ResidentWorkbook;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ResidentImportController extends Controller
{
    public function index(Request $request, AreaAccess $access)
    {
        $request->session()->forget('resident_import_nonce');

        return view('management.import.residents', ['areas' => $access->manageableRtAreas($request->user())->orderBy('code')->get(['id', 'code', 'name']), 'ready' => RwArea::query()->count() === 1 && config('personal_data.encryption_enabled')]);
    }

    public function template(ResidentWorkbook $workbook)
    {
        $rows = [
            ['05', 'CONTOH-001', '0000000000000001', 'Alamat contoh', 'Kepala Contoh', '0000000000000002', 'Kepala Keluarga', 'Kota Contoh', '1990-01-01', 'Laki-laki', 'Kepercayaan', 'Kawin', 'SMA/SMK', 'Karyawan swasta', 'Tetap', ''],
            ['05', 'CONTOH-001', '0000000000000001', 'Alamat contoh', 'Anggota Contoh', '0000000000000003', 'Anak', 'Kota Contoh', '2010-01-01', 'Perempuan', 'Kepercayaan', 'Belum Kawin', 'SMP/MTs', 'Pelajar', 'Tetap', ''],
        ];

        return response()->download($workbook->create($rows), 'template-import-warga.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store'])->deleteFileAfterSend(true);
    }

    public function preview(Request $request, ResidentWorkbook $workbook, ResidentImportValidator $validator, AreaAccess $access)
    {
        if (! config('personal_data.encryption_enabled')) {
            $this->fail(['file' => 'Penyimpanan terenkripsi harus diaktifkan sebelum impor data warga.']);
        }
        $request->session()->forget('resident_import_nonce');
        $this->validateInput($request, ['file' => ['required', 'file', 'max:1024']]);
        if (strtolower($request->file('file')->getClientOriginalExtension()) !== 'xlsx') {
            $this->fail(['file' => 'Unggah berkas .xlsx dari template yang disediakan.']);
        }
        try {
            $source = $workbook->read($request->file('file')->getRealPath());
        } catch (RuntimeException $exception) {
            $this->fail(['file' => $exception->getMessage()]);
        }
        $json = json_encode($source, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (strlen($json) > 524288) {
            $this->fail(['file' => 'Isi impor terlalu besar. Bagi workbook menjadi beberapa berkas yang lebih kecil.']);
        }
        $result = $validator->validate($request->user(), $source);
        $token = null;
        if (! $result['errors']) {
            $nonce = Str::random(40);
            $request->session()->put('resident_import_nonce', hash('sha256', $nonce));
            $token = Crypt::encryptString(json_encode(['actor_id' => $request->user()->id, 'session' => hash('sha256', $request->session()->getId()), 'nonce' => $nonce, 'expires' => now()->timestamp + 600, 'rows' => $source], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        }

        return view('management.import.residents', ['areas' => $access->manageableRtAreas($request->user())->orderBy('code')->get(['id', 'code', 'name']), 'ready' => RwArea::query()->count() === 1 && config('personal_data.encryption_enabled'), 'result' => $result, 'token' => $token]);
    }

    public function store(Request $request, ResidentImportValidator $validator)
    {
        if (! config('personal_data.encryption_enabled')) {
            $this->fail(['import' => 'Penyimpanan terenkripsi harus aktif sebelum impor.']);
        }
        $this->validateInput($request, ['import_token' => ['required', 'string', 'max:1000000']]);
        try {
            $payload = json_decode(Crypt::decryptString($request->input('import_token')), true, 64, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $this->fail(['import' => 'Pratinjau tidak valid. Unggah dan periksa kembali berkas.']);
        }
        if (! is_array($payload) || ! isset($payload['actor_id'], $payload['session'], $payload['nonce'], $payload['expires'], $payload['rows']) || $payload['actor_id'] !== $request->user()->id || ! hash_equals($payload['session'], hash('sha256', $request->session()->getId())) || ! hash_equals((string) $request->session()->get('resident_import_nonce'), hash('sha256', $payload['nonce'])) || $payload['expires'] < now()->timestamp || ! is_array($payload['rows'])) {
            $this->fail(['import' => 'Pratinjau kedaluwarsa atau berasal dari sesi lain. Unggah dan periksa kembali berkas.']);
        }
        try {
            $count = DB::transaction(function () use ($request, $payload, $validator) {
                $rw = RwArea::query()->orderBy('id')->lockForUpdate()->get();
                if ($rw->count() !== 1) {
                    $this->fail(['import' => 'Impor memerlukan tepat satu RW.']);
                }
                $result = $validator->validate($request->user(), $payload['rows'], true);
                if ($result['errors']) {
                    $this->fail(['import' => 'Data/wilayah berubah atau berkonflik sejak pratinjau. Periksa kembali workbook sebelum mengimpor.']);
                }
                $households = [];
                foreach ($result['groups'] as $key => $group) {
                    $households[$key] = $group['existing_id'] ? Household::query()->findOrFail($group['existing_id']) : Household::query()->create(Arr::only($group, ['rt_area_id', 'household_code', 'family_card_number', 'family_card_digest', 'address']));
                    if (! $group['existing_id']) {
                        AuditEvent::record($request->user(), 'household.created', $households[$key]);
                    }
                }
                foreach ($result['rows'] as $row) {
                    $person = $households[$row['group_key']]->residents()->create(Arr::only($row, ['full_name', 'national_id', 'national_id_digest', 'relationship', 'birthplace', 'birth_date', 'gender', 'religion', 'marital_status', 'education', 'occupation', 'residency_status', 'phone']));
                    AuditEvent::record($request->user(), 'resident.created', $person);
                }
                AuditEvent::record($request->user(), 'residents.imported', $rw->first());

                return count($result['rows']);
            });
        } catch (UniqueConstraintViolationException) {
            $this->fail(['import' => 'Data sudah tersimpan atau terjadi konflik bersamaan. Tidak ada baris impor tambahan yang disimpan.']);
        }
        $request->session()->forget('resident_import_nonce');

        return redirect()->route('residents.index')->with('status', $count.' warga berhasil diimpor.');
    }

    private function validateInput(Request $request, array $rules): void
    {
        try {
            $request->validate($rules);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('residents.import.index'));
        }
    }

    private function fail(array $messages): never
    {
        throw ValidationException::withMessages($messages)->redirectTo(route('residents.import.index'));
    }
}
