<div class="form-grid">
<label class="block text-sm font-medium">Nama lengkap
    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="full_name" maxlength="160" value="{{ old('full_name', $editingResident?->full_name) }}" required>
</label>
<label class="block text-sm font-medium">NIK (16 digit){{ $editingResident?->national_id ? ' - kosongkan jika tidak diubah' : '' }}
    @if ($editingResident?->national_id)<span class="mt-1 block font-normal text-slate-500">Saat ini: {{ \App\Support\PersonalDataMask::number($editingResident->national_id) }}</span>@endif
    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="national_id" inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" autocomplete="off" @required(! $editingResident?->national_id)>
</label>
<label class="block text-sm font-medium">Tempat lahir
    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="birthplace" maxlength="160" value="{{ old('birthplace', $editingResident?->birthplace) }}" required>
</label>
<label class="block text-sm font-medium">Tanggal lahir
    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" type="date" name="birth_date" value="{{ old('birth_date', $editingResident?->birth_date?->format('Y-m-d')) }}" max="{{ now()->toDateString() }}" required>
</label>
<label class="block text-sm font-medium">Jenis kelamin
    <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="gender" required>
        <option value="">Pilih jenis kelamin</option>
        @foreach (['Laki-laki', 'Perempuan'] as $value)
            <option value="{{ $value }}" @selected(old('gender', $editingResident?->gender) === $value)>{{ $value }}</option>
        @endforeach
    </select>
</label>
<label class="block text-sm font-medium">Agama atau kepercayaan
    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="religion" list="religion-options" maxlength="80" value="{{ $editingResident?->religion }}" required>
</label>
<datalist id="religion-options">
    @foreach (['Islam', 'Kristen Protestan', 'Kristen Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Kepercayaan'] as $value)
        <option value="{{ $value }}">
    @endforeach
</datalist>
<label class="block text-sm font-medium">Status perkawinan
    <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="marital_status" required>
        <option value="">Pilih status</option>
        @foreach (['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'] as $value)
            <option value="{{ $value }}" @selected(old('marital_status', $editingResident?->marital_status) === $value)>{{ $value }}</option>
        @endforeach
    </select>
</label>
<label class="block text-sm font-medium">Pendidikan
    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="education" list="education-options" maxlength="80" value="{{ old('education', $editingResident?->education) }}" required>
</label>
<datalist id="education-options">
    @foreach (['Belum sekolah', 'PAUD/TK/Kelompok Bermain', 'SD/MI', 'SMP/MTs', 'SMA/SMK', 'Diploma', 'Sarjana', 'Magister', 'Doktor'] as $value)
        <option value="{{ $value }}">
    @endforeach
</datalist>
<label class="block text-sm font-medium">Pekerjaan
    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="occupation" list="occupation-options" maxlength="160" value="{{ old('occupation', $editingResident?->occupation) }}" required>
</label>
<datalist id="occupation-options">
    @foreach (['Buruh', 'Karyawan swasta', 'Wiraswasta', 'Guru', 'PNS', 'Pedagang', 'Ibu rumah tangga', 'Pelajar', 'Mahasiswa', 'Belum bekerja'] as $value)
        <option value="{{ $value }}">
    @endforeach
</datalist>
<label class="block text-sm font-medium">Status kependudukan
    <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="residency_status" required>
        <option value="">Pilih status</option>
        @foreach (['Tetap', 'Kontrak/Kos'] as $value)
            <option value="{{ $value }}" @selected(old('residency_status', $editingResident?->residency_status) === $value)>{{ $value }}</option>
        @endforeach
    </select>
</label>
<label class="block text-sm font-medium">Nomor telepon (opsional){{ $editingResident?->phone ? ' - kosongkan jika tidak diubah' : '' }}
    @if ($editingResident?->phone)<span class="mt-1 block font-normal text-slate-500">Saat ini: {{ \App\Support\PersonalDataMask::number($editingResident->phone) }}</span>@endif
    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="phone" type="tel" maxlength="32" autocomplete="off">
</label>
@if ($editingResident?->phone)<label class="col-span-full flex items-center gap-2 text-sm font-normal"><input type="checkbox" name="clear_phone" value="1">Hapus nomor telepon yang tersimpan</label>@endif
</div>
