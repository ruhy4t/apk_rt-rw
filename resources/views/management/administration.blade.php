@extends('layouts.app')

@section('title', request()->routeIs('users.index') ? 'Pengguna' : 'Pengaturan wilayah')

@section('content')
    <h1>{{ request()->routeIs('users.index') ? 'Pengguna' : 'Pengaturan wilayah' }}</h1>
    <p class="mt-2 text-sm text-slate-600">{{ request()->routeIs('users.index') ? 'Kelola akun sesuai wilayah yang ditugaskan. Kata sandi awal dan hasil reset wajib diganti saat masuk.' : 'Isi identitas satu RW, lalu tambahkan RT yang berada di bawah RW tersebut. Nama wilayah dan nama ketua diisi pada bagian yang berbeda.' }}</p>

    @if (request()->routeIs('admin.setup') && auth()->user()->hasRole('super_admin'))
    <ol class="mt-6 flex flex-wrap gap-4 text-sm" aria-label="Urutan pengaturan wilayah">
        <li><a class="text-link" href="#rw-identity">1. Identitas RW</a></li>
        <li><a class="text-link" href="#rt-list">2. Daftar RT</a></li>
    </ol>
    @unless ($chairmanRtReady && $chairmanRwReady)
        <div class="notice notice-error mt-6" role="status">Pengaturan Ketua RT/RW belum aktif. Terapkan pembaruan database dari file UPDATE-DATABASE.sql terlebih dahulu. Wilayah masih dapat dibuat tanpa nama ketua.</div>
    @endunless
    @if ($rwAreas->count() > 1)
        <div class="notice notice-error mt-6" role="alert"><p class="font-semibold">Tercatat {{ $rwAreas->count() }} RW, bukan {{ $rwAreas->count() }} RT.</p><p class="mt-2">Data lama masih berisi lebih dari satu RW. Karena aplikasi memakai satu RW, Tambah RT baru tersedia setelah susunan ini dikoreksi. Jika suatu baris seharusnya RT, gunakan Koreksi menjadi RT pada baris tersebut. Data yang sudah terhubung harus diperiksa terlebih dahulu.</p></div>
    @elseif ($rwAreas->count() === 1)
        <p class="mt-6 text-sm text-slate-600">RW yang digunakan: <strong class="text-slate-900">{{ $rwAreas->first()->name }} · {{ $rwAreas->first()->code }}</strong>. RT baru ditambahkan ke RW ini.</p>
    @endif
    <div class="mt-6 flex flex-wrap gap-3">
        @if ($rwAreas->isEmpty())
        <button class="rounded-lg bg-brand-700 px-4 py-2.5 font-semibold text-white hover:bg-brand-800" type="button" data-open-dialog="create-rw"><x-icon name="plus" /><span data-button-label>Tambah wilayah RW</span></button>
        @elseif ($rwAreas->count() === 1)
        <button class="rounded-lg border border-brand-700 px-4 py-2.5 font-semibold text-brand-800 hover:bg-brand-50" type="button" data-open-dialog="create-rt"><x-icon name="plus" /><span data-button-label>Tambah wilayah RT</span></button>
        @endif
    </div>
    <noscript><p class="mt-3 text-sm text-rose-700">Aktifkan JavaScript untuk membuka formulir wilayah.</p></noscript>
    @if ($rwAreas->isEmpty())
    <dialog id="create-rw" aria-labelledby="create-rw-title" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-lg border border-slate-200 bg-white p-6 shadow-xl backdrop:bg-slate-950/50" @if ($errors->any() && old('_form') === 'create-rw') data-auto-open @endif>
        <div class="flex items-center justify-between gap-4">
            <h2 id="create-rw-title" class="text-xl font-bold">Tambah wilayah RW</h2>
            <button class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" type="button" data-close-dialog><x-icon name="close" /><span data-button-label>Tutup</span></button>
        </div>
        @if ($errors->any() && old('_form') === 'create-rw')
            <div role="alert" class="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-900">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <form method="POST" action="{{ route('admin.rw.store') }}">
            @csrf
            <input type="hidden" name="_form" value="create-rw">
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="text-sm font-medium">Kode RW
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="code" maxlength="32" required>
                </label>
                <label class="text-sm font-medium">Nama wilayah RW
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="name" maxlength="120" required>
                </label>
                <div class="space-y-3 sm:col-span-2"><x-rw-identity-fields :ready="$rwIdentityReady" /></div>
                <label class="text-sm font-medium sm:col-span-2">Nama Ketua RW (opsional)
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="chairman_name" maxlength="160" autocomplete="off" @disabled(!$chairmanRwReady)>
                </label>
            </div>
            <p class="mt-3 text-sm text-slate-600">Boleh ditetapkan kemudian. Pengumuman dan agenda seluruh RW baru dapat diterbitkan setelah Ketua RW ditetapkan. Nama ini tidak membuat akun login.</p>
            <button class="mt-4 rounded-lg bg-brand-700 px-4 py-2.5 font-semibold text-white" type="submit"><x-icon name="save" /><span data-button-label>Simpan RW</span></button>
        </form>
    </dialog>
    @endif
    @if ($rwAreas->count() === 1)
    <dialog id="create-rt" aria-labelledby="create-rt-title" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-lg border border-slate-200 bg-white p-6 shadow-xl backdrop:bg-slate-950/50" @if ($errors->any() && old('_form') === 'create-rt') data-auto-open @endif>
        <div class="flex items-center justify-between gap-4">
            <h2 id="create-rt-title" class="text-xl font-bold">Tambah wilayah RT</h2>
            <button class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" type="button" data-close-dialog><x-icon name="close" /><span data-button-label>Tutup</span></button>
        </div>
        @if ($errors->any() && old('_form') === 'create-rt')
            <div role="alert" class="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-900">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <form method="POST" action="{{ route('admin.rt.store') }}">
            @csrf
            <input type="hidden" name="_form" value="create-rt">
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <label class="text-sm font-medium sm:col-span-3">Wilayah RW
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" value="{{ $rwAreas->first()->name }} · {{ $rwAreas->first()->code }}" readonly>
                    <input type="hidden" name="rw_area_id" value="{{ $rwAreas->first()->id }}">
                </label>
                <label class="text-sm font-medium">Kode RT
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="code" maxlength="32" required>
                </label>
                <label class="text-sm font-medium sm:col-span-2">Nama wilayah RT
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="name" maxlength="120" required>
                </label>
                <label class="text-sm font-medium sm:col-span-3">Nama Ketua RT (opsional)
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="chairman_name" maxlength="160" autocomplete="off" @disabled(!$chairmanRtReady)>
                </label>
            </div>
            <p class="mt-3 text-sm text-slate-600">Wilayah dan draf tetap bisa dibuat tanpa ketua. Penerbitan dokumen layanan, pengumuman, dan agenda RT menunggu penetapan Ketua RT. Nama ini tidak membuat akun login.</p>
            <button class="mt-4 rounded-lg bg-brand-700 px-4 py-2.5 font-semibold text-white" type="submit"><x-icon name="save" /><span data-button-label>Simpan RT</span></button>
        </form>
    </dialog>
    @endif
    @endif

    @if (request()->routeIs('users.index'))
    <button class="mt-6 rounded-lg bg-brand-700 px-4 py-2.5 font-semibold text-white hover:bg-brand-800" type="button" data-open-dialog="create-user"><x-icon name="plus" /><span data-button-label>Tambah pengguna</span></button>
    <noscript><p class="mt-3 text-sm text-rose-700">Aktifkan JavaScript untuk membuka formulir pengguna.</p></noscript>
    <dialog id="create-user" aria-labelledby="create-user-title" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-3xl overflow-y-auto rounded-lg border border-slate-200 bg-white p-6 shadow-xl backdrop:bg-slate-950/50" @if (($errors->any() && old('_form') === 'create-user') || request()->has('resident_page')) data-auto-open @endif>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 id="create-user-title" class="text-xl font-bold">Tambah pengguna</h2>
            <p class="mt-1 text-sm text-slate-600">Sampaikan kata sandi awal melalui kanal yang aman.</p>
        </div>
        <button class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" type="button" data-close-dialog><x-icon name="close" /><span data-button-label>Tutup</span></button>
    </div>
    @if ($errors->any() && old('_form') === 'create-user')
        <div role="alert" class="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-900">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    <form class="mt-4" method="POST" action="{{ route('users.store') }}">
        @csrf
        <input type="hidden" name="_form" value="create-user">
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="text-sm font-medium">Nama
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="name" maxlength="160" value="{{ old('_form') === 'create-user' ? old('name') : '' }}" required>
            </label>
            <label class="text-sm font-medium">Nama pengguna
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="username" maxlength="80" autocomplete="off" value="{{ old('_form') === 'create-user' ? old('username') : '' }}" required>
            </label>
            <label class="text-sm font-medium">Peran
                <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="role" required>
                    <option value="resident" @selected(old('role') === 'resident')>Warga</option>
                    @if (auth()->user()->hasRole('super_admin', 'rw_operator'))
                    <option value="rt_operator" @selected(old('role') === 'rt_operator')>Petugas RT</option>
                    @endif
                    @if (auth()->user()->hasRole('super_admin'))
                    <option value="rw_operator" @selected(old('role') === 'rw_operator')>Petugas RW</option>
                    @endif
                </select>
            </label>
            @if (auth()->user()->hasRole('super_admin'))
            <label class="text-sm font-medium">Wilayah RW (untuk petugas RW)
                <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="rw_area_id">
                    <option value="">Tidak ditetapkan</option>
                    @foreach ($rwAreas as $rw)
                        <option value="{{ $rw->id }}" @selected((string) old('rw_area_id') === (string) $rw->id)>{{ $rw->name }} · {{ $rw->code }}</option>
                    @endforeach
                </select>
            </label>
            @endif
            @if (auth()->user()->hasRole('super_admin', 'rw_operator'))
            <label class="text-sm font-medium">Wilayah RT (untuk petugas RT)
                <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="rt_area_id">
                    <option value="">Tidak ditetapkan</option>
                    @foreach ($rwAreas as $rw)
                        @foreach ($rw->rtAreas as $rt)
                            <option value="{{ $rt->id }}" @selected((string) old('rt_area_id') === (string) $rt->id)>{{ $rt->name }} · {{ $rw->name }}</option>
                        @endforeach
                    @endforeach
                </select>
            </label>
            @endif
            <label class="text-sm font-medium">Data warga (untuk akun warga)
                <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="resident_id">
                    <option value="">Tidak ditetapkan</option>
                    @foreach ($unlinkedResidents as $resident)
                        <option value="{{ $resident->id }}" @selected((string) old('resident_id') === (string) $resident->id)>{{ $resident->full_name }} · {{ $resident->household->household_code }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium">Kata sandi awal (min. 12 karakter)
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" type="password" name="password" minlength="12" maxlength="255" autocomplete="new-password" required>
            </label>
            <label class="text-sm font-medium">Ulangi kata sandi
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" type="password" name="password_confirmation" minlength="12" maxlength="255" autocomplete="new-password" required>
            </label>
        </div>
        <button class="mt-4 rounded-lg bg-brand-700 px-4 py-2.5 font-semibold text-white" type="submit"><x-icon name="plus" /><span data-button-label>Buat akun</span></button>
    </form>
    @if ($unlinkedResidents->hasPages())
        <div class="mt-3 text-sm text-slate-600">Halaman pilihan data warga: {{ $unlinkedResidents->links() }}</div>
    @endif
    </dialog>
    @endif

    @if (request()->routeIs('admin.setup') && auth()->user()->hasRole('super_admin'))
    <section id="rw-identity" class="mt-8">
        <div class="section-heading"><h2 class="text-lg font-bold">1. Identitas RW</h2><span>Satu RW untuk aplikasi ini</span></div>
        <p class="mb-4 text-sm text-slate-600">Kode dan nama RW, alamat administrasi, serta Ketua RW ditetapkan di bagian ini. Daftar RT ada pada bagian berikutnya.</p>
        <div class="grid gap-4 {{ $rwAreas->count() > 1 ? 'lg:grid-cols-2' : '' }}">
            @forelse ($rwAreas as $rw)
                <article class="panel">
                    <h3 class="text-lg font-semibold">{{ $rw->name }} · {{ $rw->code }}</h3>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-slate-500">Kode RW</dt><dd class="font-medium">{{ $rw->code }}</dd></div>
                        <div><dt class="text-slate-500">Nama wilayah RW</dt><dd class="font-medium">{{ $rw->name }}</dd></div>
                        @foreach (['village_name' => 'Kelurahan / Desa', 'district_name' => 'Kecamatan', 'city_name' => 'Kota / Kabupaten'] as $field => $label)
                            <div><dt class="text-slate-500">{{ $label }}</dt><dd class="font-medium">{{ $rwIdentityReady && $rw->getAttribute($field) ? $rw->getAttribute($field) : 'Belum diisi' }}</dd></div>
                        @endforeach
                    </dl>
                    <x-area-editor :area="$rw" level="RW" :identity-ready="$rwIdentityReady" />
                    <p class="mt-2 text-sm text-slate-600">Ketua RW: <strong class="text-slate-900">{{ $chairmanRwReady && $rw->chairman_name ? $rw->chairman_name : 'Belum ditetapkan' }}</strong></p>
                    @if ($chairmanRwReady)
                        <details class="edit-section" @if($errors->any() && old('_form') === 'chairman-rw-'.$rw->id) open @endif>
                            <summary><x-icon name="edit" />Tetapkan / ganti Ketua RW</summary>
                            <form class="mt-3 space-y-3" method="POST" action="{{ route('admin.rw.chairman', $rw) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="_form" value="chairman-rw-{{ $rw->id }}">
                                <label class="block text-sm font-medium">Nama Ketua RW
                                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="chairman_name" value="{{ $rw->chairman_name }}" maxlength="160" required autocomplete="off">
                                </label>
                                <p class="text-xs text-slate-500">Nama dan jabatan akan dicatat saat penerbitan. Dokumen lama tetap menggunakan nama yang dicatat sebelumnya.</p>
                                <button class="button button-primary" type="submit"><x-icon name="save" /><span data-button-label>Simpan Ketua RW</span></button>
                            </form>
                        </details>
                    @endif
                    @if ($rwAreas->count() > 1)
                        <div class="mt-5 border-t border-slate-200 pt-4">
                            <p class="text-sm font-semibold">RT yang sudah terhubung: {{ $rw->rtAreas->count() }}</p>
                            <ul class="mt-2 space-y-2 text-sm">
                                @forelse ($rw->rtAreas as $rt)
                                    <li><strong>{{ $rt->name }} · {{ $rt->code }}</strong><x-area-editor :area="$rt" level="RT" />
                                <p class="mt-1">Ketua RT: <strong class="text-slate-900">{{ $chairmanRtReady && $rt->chairman_name ? $rt->chairman_name : 'Belum ditetapkan' }}</strong></p>
                                @if ($chairmanRtReady)
                                    <details class="edit-section" @if($errors->any() && old('_form') === 'chairman-rt-'.$rt->id) open @endif>
                                        <summary><x-icon name="edit" />Tetapkan / ganti Ketua RT</summary>
                                        <form class="mt-3 space-y-3" method="POST" action="{{ route('admin.rt.chairman', $rt) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="_form" value="chairman-rt-{{ $rt->id }}">
                                            <label class="block font-medium">Nama Ketua RT
                                                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="chairman_name" value="{{ $rt->chairman_name }}" maxlength="160" required autocomplete="off">
                                            </label>
                                            <p class="text-xs text-slate-500">Untuk tanda tangan manual. Penggantian ketua tidak mengubah nama pada dokumen yang sudah diterbitkan.</p>
                                            <button class="button button-primary" type="submit"><x-icon name="save" /><span data-button-label>Simpan Ketua RT</span></button>
                                        </form>
                                    </details>
                                @endif
                                    </li>
                                @empty<li>Belum ada RT.</li>@endforelse
                            </ul>
                        </div>
                        @php
                            $hasIdentity = $rwIdentityReady && collect(['village_name', 'district_name', 'city_name'])->contains(fn ($field) => filled($rw->getAttribute($field)));
                            $canCorrect = $rw->rtAreas->isEmpty() && (int) $rw->assigned_rw_accounts_count === 0 && ! $hasIdentity;
                            $correctionFormId = 'correct-rw-'.$rw->id;
                            $suggestedRtCode = preg_replace('/^RT[.\s_-]*/i', '', $rw->code);
                        @endphp
                        @if ($canCorrect)
                            <details class="edit-section" @if($errors->any() && old('_form') === $correctionFormId) open @endif>
                                <summary><x-icon name="edit" />Koreksi menjadi RT</summary>
                                <p class="mt-3 text-sm text-slate-600">Gunakan hanya jika baris RW ini memang salah dibuat dan seharusnya merupakan RT. Periksa RW tujuan sebelum menyimpan.</p>
                                <form class="mt-3 space-y-3" method="POST" action="{{ route('admin.rw.convert-to-rt', $rw) }}">
                                    @csrf<input type="hidden" name="_form" value="{{ $correctionFormId }}">
                                    <label class="block text-sm font-medium">RW tujuan untuk RT ini
                                        <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="target_rw_area_id" required>
                                            <option value="">Pilih RW utama</option>
                                            @foreach ($rwAreas as $targetRw)
                                                @if ($targetRw->id !== $rw->id)<option value="{{ $targetRw->id }}" @selected(old('_form') === $correctionFormId && (string) old('target_rw_area_id') === (string) $targetRw->id)>{{ $targetRw->name }} · {{ $targetRw->code }}</option>@endif
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="block text-sm font-medium">Kode RT yang benar<input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="code" value="{{ old('_form') === $correctionFormId ? old('code', $suggestedRtCode) : $suggestedRtCode }}" maxlength="32" required></label>
                                    <label class="block text-sm font-medium">Nama wilayah RT<input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="name" value="RT {{ $suggestedRtCode }}" maxlength="120" required></label>
                                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirm_conversion" value="1" required><span>Saya setuju baris RW {{ $rw->code }} ini dihapus dan diganti dengan RT baru di bawah RW tujuan yang dipilih, setelah pemeriksaan data berhasil.</span></label>
                                    <button class="button button-primary" type="submit"><x-icon name="save" /><span data-button-label>Koreksi RW ini menjadi RT</span></button>
                                </form>
                            </details>
                        @else
                            <p class="mt-4 text-xs text-slate-600">Koreksi menjadi RT belum tersedia untuk baris ini karena sudah memiliki RT, akun RW, atau identitas administrasi. Data tersebut perlu diperiksa terlebih dahulu.</p>
                        @endif
                    @endif
                </article>
            @empty
                <p class="empty-state">Identitas RW belum dibuat. Gunakan Tambah wilayah RW di atas untuk memulai.</p>
            @endforelse
        </div>
    </section>
    <section id="rt-list" class="mt-8">
        <div class="section-heading"><h2 class="text-lg font-bold">2. Daftar RT</h2>
            @if ($rwAreas->count() === 1)<button class="button button-primary" type="button" data-open-dialog="create-rt"><x-icon name="plus" /><span data-button-label>Tambah RT baru</span></button>
            @else<button class="button" type="button" disabled aria-disabled="true"><x-icon name="plus" />Tambah RT baru</button>@endif
        </div>
        @if ($rwAreas->count() === 1)
            <p class="mb-4 text-sm text-slate-600">Semua RT berikut berada di bawah {{ $rwAreas->first()->name }} · {{ $rwAreas->first()->code }}. Klik Tambah RT baru untuk menambahkan nomor RT lain.</p>
            <div class="grid gap-4 md:grid-cols-2">
                @forelse ($rwAreas->first()->rtAreas as $rt)
                    <article class="panel"><h3 class="font-semibold">{{ $rt->name }} · {{ $rt->code }}</h3><x-area-editor :area="$rt" level="RT" />
                                <p class="mt-1">Ketua RT: <strong class="text-slate-900">{{ $chairmanRtReady && $rt->chairman_name ? $rt->chairman_name : 'Belum ditetapkan' }}</strong></p>
                                @if ($chairmanRtReady)
                                    <details class="edit-section" @if($errors->any() && old('_form') === 'chairman-rt-'.$rt->id) open @endif>
                                        <summary><x-icon name="edit" />Tetapkan / ganti Ketua RT</summary>
                                        <form class="mt-3 space-y-3" method="POST" action="{{ route('admin.rt.chairman', $rt) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="_form" value="chairman-rt-{{ $rt->id }}">
                                            <label class="block font-medium">Nama Ketua RT
                                                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="chairman_name" value="{{ $rt->chairman_name }}" maxlength="160" required autocomplete="off">
                                            </label>
                                            <p class="text-xs text-slate-500">Untuk tanda tangan manual. Penggantian ketua tidak mengubah nama pada dokumen yang sudah diterbitkan.</p>
                                            <button class="button button-primary" type="submit"><x-icon name="save" /><span data-button-label>Simpan Ketua RT</span></button>
                                        </form>
                                    </details>
                                @endif
                    </article>
                @empty<p class="empty-state">Belum ada RT. Klik Tambah RT baru untuk membuat RT pertama.</p>@endforelse
            </div>
        @elseif ($rwAreas->isEmpty())
            <p class="empty-state">Isi identitas RW terlebih dahulu. Setelah RW tersimpan, tombol Tambah RT baru akan aktif.</p>
        @else
            <p class="empty-state">Tambah RT belum aktif karena masih ada {{ $rwAreas->count() }} RW. Koreksi baris yang seharusnya RT melalui bagian Identitas RW di atas. Setelah tersisa satu RW, tombol ini akan aktif.</p>
        @endif
    </section>
    @endif

    @if (request()->routeIs('users.index'))
    <section class="mt-8">
        <h2 class="text-lg font-bold">Akun aplikasi</h2>
        <div class="mt-3 overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="w-full min-w-[42rem] text-left text-sm">
                <thead class="bg-slate-100 text-slate-600"><tr><th class="p-3">Nama</th><th class="p-3">Nama pengguna</th><th class="p-3">Peran</th><th class="p-3">Cakupan</th><th class="p-3">Status</th><th class="p-3">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($users as $user)
                        <tr>
                            <td class="p-3">{{ $user->name }}</td>
                            <td class="p-3">{{ $user->username }}</td>
                            <td class="p-3">{{ match ($user->role) { 'super_admin' => 'Administrator', 'rw_operator' => 'Pengurus RW', 'rt_operator' => 'Pengurus RT', 'resident' => 'Warga', default => $user->role } }}</td>
                            <td class="p-3">{{ $user->rtArea?->name ?? $user->rwArea?->name ?? ($user->role === 'resident' ? 'Rumah tangga' : 'Semua wilayah') }}</td>
                            <td class="p-3"><span class="badge {{ $user->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="p-3">
                                @unless (auth()->user()->is($user))
                                    <form method="POST" action="{{ route('users.status', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                        <button class="font-semibold text-brand-800 underline" type="submit"><x-icon name="power" /><span data-button-label>{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</span></button>
                                    </form>
                                    <button class="mt-2 font-semibold text-brand-800 underline" type="button" data-open-dialog="edit-user-{{ $user->id }}"><x-icon name="edit" /><span data-button-label>Ubah akun</span></button>
                                    <dialog id="edit-user-{{ $user->id }}" aria-labelledby="edit-user-title-{{ $user->id }}" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-lg border border-slate-200 bg-white p-6 text-slate-900 shadow-xl backdrop:bg-slate-950/50" @if ($errors->any() && old('_form') === 'edit-user-'.$user->id) data-auto-open @endif>
                                        <div class="flex items-center justify-between gap-4">
                                            <h3 id="edit-user-title-{{ $user->id }}" class="text-lg font-bold">Ubah akun {{ $user->username }}</h3>
                                            <button class="rounded-lg px-3 py-2 font-semibold text-slate-600 hover:bg-slate-100" type="button" data-close-dialog><x-icon name="close" /><span data-button-label>Tutup</span></button>
                                        </div>
                                        <p class="mt-2 text-sm text-slate-600">Nama pengguna tetap. Perbarui nama atau reset kata sandi di bawah ini.</p>
                                        @if ($errors->any() && old('_form') === 'edit-user-'.$user->id)
                                            <div role="alert" class="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-900">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                                        @endif
                                        <form class="mt-2 space-y-2" method="POST" action="{{ route('users.update', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="_form" value="edit-user-{{ $user->id }}">
                                            <label class="block">Nama
                                                <input class="w-full rounded-lg border border-slate-300 px-2 py-1" name="name" value="{{ $user->name }}" maxlength="160" required>
                                            </label>
                                            <button class="font-semibold text-brand-800 underline" type="submit"><x-icon name="save" /><span data-button-label>Simpan nama</span></button>
                                        </form>
                                        <form class="mt-3 space-y-2" method="POST" action="{{ route('users.password', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="_form" value="edit-user-{{ $user->id }}">
                                            <label class="block">Kata sandi sementara (min. 12 karakter)
                                                <input class="w-full rounded-lg border border-slate-300 px-2 py-1" type="password" name="password" minlength="12" maxlength="255" autocomplete="new-password" required>
                                            </label>
                                            <label class="block">Ulangi kata sandi
                                                <input class="w-full rounded-lg border border-slate-300 px-2 py-1" type="password" name="password_confirmation" minlength="12" maxlength="255" autocomplete="new-password" required>
                                            </label>
                                            <button class="font-semibold text-rose-800 underline" type="submit"><x-icon name="reset" /><span data-button-label>Reset kata sandi</span></button>
                                        </form>
                                    </dialog>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
    </section>
    @endif
@endsection
