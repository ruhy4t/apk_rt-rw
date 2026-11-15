@extends('layouts.app')

@section('title', request()->routeIs('users.index') ? 'Pengguna' : 'Pengaturan wilayah')

@section('content')
    <h1>{{ request()->routeIs('users.index') ? 'Pengguna' : 'Pengaturan wilayah' }}</h1>
    <p class="mt-2 text-sm text-slate-600">{{ request()->routeIs('users.index') ? 'Kelola akun sesuai wilayah yang ditugaskan. Kata sandi awal dan hasil reset wajib diganti saat masuk.' : 'Aplikasi ini melayani satu RW dengan beberapa RT. Tetapkan RW sekali, lalu kelola daftar RT di bawahnya.' }}</p>

    @if (request()->routeIs('admin.setup') && auth()->user()->hasRole('super_admin'))
    @if ($rwAreas->count() > 1)
        <div class="notice notice-error mt-6" role="alert">Data lama masih berisi lebih dari satu RW. Penambahan wilayah dinonaktifkan sampai RW yang digunakan ditentukan. Data lama tetap dipertahankan.</div>
    @elseif ($rwAreas->count() === 1)
        <p class="mt-6 text-sm text-slate-600">RW terdaftar: <strong class="text-slate-900">{{ $rwAreas->first()->name }} · {{ $rwAreas->first()->code }}</strong>. Penambahan RW lain tidak tersedia.</p>
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
                <label class="text-sm font-medium">Nama
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="name" maxlength="120" required>
                </label>
            </div>
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
                <label class="text-sm font-medium sm:col-span-2">Nama RT
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="name" maxlength="120" required>
                </label>
            </div>
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
    <section class="mt-8">
        <h2 class="text-lg font-bold">Wilayah terdaftar</h2>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($rwAreas as $rw)
                <article class="rounded-lg border border-slate-200 bg-white p-4">
                    <h3 class="font-semibold">{{ $rw->name }} · {{ $rw->code }}</h3>
                    <ul class="mt-2 space-y-1 text-sm text-slate-600">
                        @forelse ($rw->rtAreas as $rt)
                            <li>{{ $rt->name }} · {{ $rt->code }}</li>
                        @empty
                            <li>Belum ada RT.</li>
                        @endforelse
                    </ul>
                </article>
            @empty
                <p class="text-sm text-slate-500">Belum ada wilayah RW.</p>
            @endforelse
        </div>
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
