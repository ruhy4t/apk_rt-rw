@extends('layouts.app')

@section('title', 'Impor data warga')

@section('content')
    <div class="page-heading"><div><h1>Impor data warga</h1><p>Tambahkan rumah tangga dan anggota keluarga dari Excel. Data lama tidak ditimpa.</p></div><a class="button" href="{{ route('residents.index') }}"><x-icon name="arrow-left" />Data warga</a></div>
    <ol class="mb-6 flex flex-wrap gap-4 text-sm" aria-label="Tahapan impor"><li><span class="badge">1</span> Unggah</li><li><span class="badge">2</span> Periksa data</li><li><span class="badge">3</span> Konfirmasi simpan</li></ol>
    @unless ($ready && $areas->isNotEmpty())
        <div class="notice notice-error" role="status">Siapkan tepat satu RW, wilayah RT yang ditugaskan, dan aktifkan penyimpanan terenkripsi sebelum mengimpor. Hubungi administrator bila belum tersedia.</div>
    @endunless
    <section class="panel">
        <div class="section-heading"><h2>Gunakan template yang disediakan</h2><a class="button" href="{{ route('residents.import.template') }}"><x-icon name="download" />Unduh template .xlsx</a></div>
        <ul class="list-inside list-disc space-y-2 text-sm text-slate-600">
            <li>Maksimal 200 warga dan 1 MiB per berkas. Gunakan satu sheet bernama <strong>Warga</strong>; nama dan urutan kolom jangan diubah.</li>
            <li>Semua kolom berformat <strong>Text</strong>. NIK/KK harus tepat 16 digit. Tanggal lahir memakai <strong>YYYY-MM-DD</strong>, bukan nomor tanggal Excel.</li>
            <li>Hapus/ganti dua baris contoh. Jangan gunakan formula, makro, tautan eksternal, atau sel gabungan.</li>
            <li>Untuk keluarga baru, isi tepat satu <strong>Kepala Keluarga</strong>. Untuk keluarga lama, nomor KK harus cocok dan kepala tidak boleh diganti melalui impor.</li>
        </ul>
        @if ($areas->isNotEmpty())<p class="mt-4 text-sm"><strong>Kode RT yang dapat diimpor:</strong> @foreach ($areas as $area){{ $area->code }} ({{ $area->name }}){{ $loop->last ? '' : ', ' }}@endforeach</p>@endif
        <form class="mt-6 flex flex-wrap items-end gap-4" method="POST" action="{{ route('residents.import.preview') }}" enctype="multipart/form-data">
            @csrf
            <label class="block text-sm font-medium">Berkas Excel .xlsx<input class="mt-2 block max-w-full text-sm" type="file" name="file" accept=".xlsx" required @disabled(! $ready || $areas->isEmpty())></label>
            <button class="button button-primary" type="submit" @disabled(! $ready || $areas->isEmpty())><x-icon name="upload" /><span data-button-label>Periksa berkas</span></button>
        </form>
    </section>
    @isset ($result)
        @if ($result['errors'])
            <section class="notice notice-error mt-6" role="alert"><h2>Perbaiki workbook sebelum disimpan</h2><p class="mt-2 text-sm">Tidak ada data yang diimpor. Perbaiki baris berikut, lalu unggah kembali.</p><ul class="mt-3 space-y-2 text-sm">@foreach ($result['errors'] as $error)<li><strong>{{ $error['row'] ? 'Baris '.$error['row'].':' : 'Workbook:' }}</strong> {{ implode(' ', $error['messages']) }}</li>@endforeach</ul></section>
        @else
            <section class="mt-8">
                <div class="section-heading"><h2>Pratinjau impor</h2><span>{{ count($result['rows']) }} warga · {{ count($result['groups']) }} rumah tangga</span></div>
                <p class="mb-4 text-sm text-slate-600">Belum ada data yang disimpan. Nomor disamarkan di server. Konfirmasi berlaku 10 menit pada sesi ini; data diperiksa ulang saat disimpan.</p>
                <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white"><table class="w-full min-w-[44rem] text-left text-sm"><thead class="bg-slate-100 text-slate-600"><tr><th class="p-3">Baris</th><th class="p-3">RT / keluarga</th><th class="p-3">Nama</th><th class="p-3">Hubungan</th><th class="p-3">NIK</th><th class="p-3">KK</th><th class="p-3">Telepon</th></tr></thead><tbody class="divide-y divide-slate-100">
                    @foreach ($result['rows'] as $row)<tr><td class="p-3">{{ $row['source_row'] }}</td><td class="p-3">{{ $row['rt_code'] }} / {{ $row['household_code'] }}</td><td class="p-3">{{ $row['full_name'] }}</td><td class="p-3">{{ $row['relationship'] }}</td><td class="p-3">{{ \App\Support\PersonalDataMask::number($row['national_id']) }}</td><td class="p-3">{{ \App\Support\PersonalDataMask::number($row['family_card_number']) }}</td><td class="p-3">{{ \App\Support\PersonalDataMask::number($row['phone']) ?: '—' }}</td></tr>@endforeach
                </tbody></table></div>
                <form class="form-actions" method="POST" action="{{ route('residents.import.store') }}">
                    @csrf
                    <input type="hidden" name="import_token" value="{{ $token }}">
                    <button class="button button-primary" type="submit"><x-icon name="save" /><span data-button-label>Konfirmasi impor {{ count($result['rows']) }} warga</span></button>
                    <a class="button" href="{{ route('residents.import.index') }}">Batalkan</a>
                </form>
            </section>
        @endif
    @endisset
@endsection
