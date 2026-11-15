<?php

namespace App\Services\ResidentImport;

use App\Models\Household;
use App\Models\Resident;
use App\Models\RwArea;
use App\Models\User;
use App\Services\AreaAccess;
use App\Services\IdentityDigest;
use App\Services\ResidentInputRules;
use Illuminate\Support\Facades\Validator;

final class ResidentImportValidator
{
    public function __construct(private AreaAccess $access, private IdentityDigest $digest) {}

    public function validate(User $actor, array $source, bool $locking = false): array
    {
        $errors = [];
        $rows = [];
        $groups = [];
        $niks = [];
        $cards = [];
        if (RwArea::query()->count() !== 1 || count($source) < 1 || count($source) > ResidentWorkbook::MAX_ROWS) {
            return ['rows' => [], 'groups' => [], 'errors' => [['row' => null, 'messages' => ['Impor memerlukan tepat satu RW dan 1–200 baris warga.']]]];
        }
        $areas = $this->access->manageableRtAreas($actor)->get(['id', 'code'])->keyBy('code');
        foreach ($source as $input) {
            if (! is_array($input) || ! isset($input['row'], $input['values']) || ! is_array($input['values']) || array_keys($input['values']) !== ResidentWorkbook::HEADERS || count(array_filter($input['values'], 'is_string')) !== count(ResidentWorkbook::HEADERS)) {
                return ['rows' => [], 'groups' => [], 'errors' => [['row' => null, 'messages' => ['Struktur baris impor tidak valid.']]]];
            }
            $v = $input['values'];
            $data = [
                'rt_code' => $v['kode_rt'], 'household_code' => $v['kode_rumah_tangga'],
                'family_card_number' => $v['nomor_kk'], 'address' => $v['alamat'] ?: null,
                'full_name' => $v['nama_lengkap'], 'national_id' => $v['nik'], 'relationship' => $v['hubungan_keluarga'],
                'birthplace' => $v['tempat_lahir'], 'birth_date' => $v['tanggal_lahir'], 'gender' => $v['jenis_kelamin'],
                'religion' => $v['agama'], 'marital_status' => $v['status_perkawinan'], 'education' => $v['pendidikan'],
                'occupation' => $v['pekerjaan'], 'residency_status' => $v['status_kependudukan'], 'phone' => $v['telepon'] ?: null,
            ];
            $check = Validator::make($data, [
                ...ResidentInputRules::rules(),
                'rt_code' => ['required', 'string', 'max:32'], 'household_code' => ['required', 'string', 'max:32'],
                'family_card_number' => ['required', 'string', 'regex:/^[0-9]{16}$/D'], 'address' => ['nullable', 'string', 'max:2000'],
                'relationship' => ['required', 'in:Kepala Keluarga,Istri,Suami,Anak,Famili Lain'],
            ], [
                'required' => ':attribute wajib diisi.', 'string' => ':attribute harus berupa teks.',
                'max.string' => ':attribute melebihi panjang yang diizinkan.', 'in' => ':attribute tidak sesuai pilihan yang tersedia.',
                'regex' => ':attribute harus terdiri dari tepat 16 digit.', 'date_format' => ':attribute harus memakai YYYY-MM-DD.',
                'before_or_equal' => ':attribute tidak boleh melewati hari ini.',
            ], ['full_name' => 'Nama lengkap', 'national_id' => 'NIK', 'family_card_number' => 'Nomor KK', 'birthplace' => 'Tempat lahir', 'birth_date' => 'Tanggal lahir', 'gender' => 'Jenis kelamin', 'religion' => 'Agama/kepercayaan', 'marital_status' => 'Status perkawinan', 'education' => 'Pendidikan', 'occupation' => 'Pekerjaan', 'residency_status' => 'Status kependudukan', 'phone' => 'Telepon', 'relationship' => 'Hubungan keluarga', 'rt_code' => 'Kode RT', 'household_code' => 'Kode rumah tangga', 'address' => 'Alamat']);
            $messages = $check->errors()->all();
            $area = $areas->get($data['rt_code']);
            if (! $area) {
                $messages[] = 'Kode RT tidak tersedia dalam wilayah akses Anda.';
            }
            if ($messages) {
                $errors[] = ['row' => $input['row'], 'messages' => $messages];

                continue;
            }
            $data['rt_area_id'] = $area->id;
            $data['national_id_digest'] = $this->digest->make('nik', $data['national_id']);
            $data['family_card_digest'] = $this->digest->make('kk', $data['family_card_number']);
            $data['source_row'] = $input['row'];
            if (isset($niks[$data['national_id_digest']])) {
                $errors[] = ['row' => $input['row'], 'messages' => ['NIK berulang di workbook. Tidak ada data yang disimpan.']];
            }
            $niks[$data['national_id_digest']] = $input['row'];
            $groupKey = $area->id.':'.$data['household_code'];
            if (isset($cards[$data['family_card_digest']]) && $cards[$data['family_card_digest']] !== $groupKey) {
                $errors[] = ['row' => $input['row'], 'messages' => ['Satu nomor KK tidak boleh digunakan pada dua kode rumah tangga atau RT.']];
            }
            $cards[$data['family_card_digest']] = $groupKey;
            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = ['rt_area_id' => $area->id, 'household_code' => $data['household_code'], 'family_card_number' => $data['family_card_number'], 'family_card_digest' => $data['family_card_digest'], 'address' => $data['address'], 'head_count' => 0, 'source_row' => $input['row']];
            }
            if ($groups[$groupKey]['family_card_digest'] !== $data['family_card_digest'] || ($groups[$groupKey]['address'] && $data['address'] && $groups[$groupKey]['address'] !== $data['address'])) {
                $errors[] = ['row' => $input['row'], 'messages' => ['Nomor KK/alamat untuk satu rumah tangga tidak konsisten.']];
            }
            $groups[$groupKey]['address'] ??= $data['address'];
            $groups[$groupKey]['head_count'] += $data['relationship'] === 'Kepala Keluarga' ? 1 : 0;
            $data['group_key'] = $groupKey;
            $rows[] = $data;
        }
        if ($niks) {
            foreach (Resident::query()->whereIn('national_id_digest', array_keys($niks))->pluck('national_id_digest') as $digest) {
                $errors[] = ['row' => $niks[$digest], 'messages' => ['NIK sudah terdaftar. Impor hanya menambah warga baru.']];
            }
        }
        foreach ($groups as &$group) {
            $query = Household::query()->where('rt_area_id', $group['rt_area_id'])->where('household_code', $group['household_code']);
            if ($locking) {
                $query->lockForUpdate();
            }
            $existing = $query->first();
            $group['existing_id'] = $existing?->id;
            $existingHeads = $existing ? $existing->residents()->heads()->when($locking, fn ($q) => $q->lockForUpdate())->get(['id'])->count() : 0;
            if ($existing && $existing->family_card_digest !== $group['family_card_digest']) {
                $errors[] = ['row' => $group['source_row'], 'messages' => ['Nomor KK tidak cocok dengan rumah tangga yang sudah ada. Perbarui melalui pengelolaan warga terlebih dahulu.']];
            }
            if ($existing && $group['address'] !== null && $existing->address !== $group['address']) {
                $errors[] = ['row' => $group['source_row'], 'messages' => ['Alamat rumah tangga lama tidak ditimpa melalui impor. Kosongkan kolom alamat atau samakan dengan data yang sudah tersimpan.']];
            }
            if (Household::query()->where('family_card_digest', $group['family_card_digest'])->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->exists()) {
                $errors[] = ['row' => $group['source_row'], 'messages' => ['Nomor KK sudah digunakan rumah tangga lain.']];
            }
            if ($existingHeads + $group['head_count'] !== 1) {
                $errors[] = ['row' => $group['source_row'], 'messages' => ['Setiap rumah tangga harus memiliki tepat satu Kepala Keluarga setelah impor. Pergantian kepala dilakukan melalui menu Ganti kepala keluarga.']];
            }
        }
        unset($group);

        return compact('rows', 'groups', 'errors');
    }
}
