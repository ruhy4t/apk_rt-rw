@extends('layouts.app')

@section('title', 'Pengaturan pengurus')

@section('content')
    <h1 class="text-2xl font-bold">Pengaturan wilayah dan akun</h1>
    <p class="mt-2 text-sm text-slate-600">Buat akun untuk warga/petugas dalam wilayah yang diizinkan. Kata sandi awal dan hasil reset harus diganti saat masuk; sampaikan melalui kanal yang aman, bukan tautan atau URL.</p>

    @if (auth()->user()->hasRole('super_admin'))
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <form class="rounded-2xl border border-slate-200 bg-white p-5" method="POST" action="{{ route('admin.rw.store') }}">
            @csrf
            <h2 class="font-bold">Tambah wilayah RW</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <label class="text-sm font-medium">Kode RW
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="code" maxlength="32" required>
                </label>
                <label class="text-sm font-medium">Nama
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="name" maxlength="120" required>
                </label>
            </div>
            <button class="mt-4 rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white" type="submit">Simpan RW</button>
        </form>

        <form class="rounded-2xl border border-slate-200 bg-white p-5" method="POST" action="{{ route('admin.rt.store') }}">
            @csrf
            <h2 class="font-bold">Tambah wilayah RT</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <label class="text-sm font-medium sm:col-span-3">Wilayah RW
                    <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="rw_area_id" required>
                        @foreach ($rwAreas as $rw)
                            <option value="{{ $rw->id }}">{{ $rw->name }} · {{ $rw->code }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-medium">Kode RT
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="code" maxlength="32" required>
                </label>
                <label class="text-sm font-medium sm:col-span-2">Nama RT
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="name" maxlength="120" required>
                </label>
            </div>
            <button class="mt-4 rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white" type="submit">Simpan RT</button>
        </form>
    </div>
    @endif

    <form class="mt-6 rounded-2xl border border-slate-200 bg-white p-5" method="POST" action="{{ route('users.store') }}">
        @csrf
        <h2 class="font-bold">Buat akun tanpa pendaftaran mandiri</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="text-sm font-medium">Nama
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="name" maxlength="160" required>
            </label>
            <label class="text-sm font-medium">Nama pengguna
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="username" maxlength="80" autocomplete="off" required>
            </label>
            <label class="text-sm font-medium">Peran
                <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="role" required>
                    <option value="resident">Warga</option>
                    @if (auth()->user()->hasRole('super_admin', 'rw_operator'))
                    <option value="rt_operator">Petugas RT</option>
                    @endif
                    @if (auth()->user()->hasRole('super_admin'))
                    <option value="rw_operator">Petugas RW</option>
                    @endif
                </select>
            </label>
            @if (auth()->user()->hasRole('super_admin'))
            <label class="text-sm font-medium">Wilayah RW (untuk petugas RW)
                <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="rw_area_id">
                    <option value="">Tidak ditetapkan</option>
                    @foreach ($rwAreas as $rw)
                        <option value="{{ $rw->id }}">{{ $rw->name }} · {{ $rw->code }}</option>
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
                            <option value="{{ $rt->id }}">{{ $rt->name }} · {{ $rw->name }}</option>
                        @endforeach
                    @endforeach
                </select>
            </label>
            @endif
            <label class="text-sm font-medium">Data warga (untuk akun warga)
                <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="resident_id">
                    <option value="">Tidak ditetapkan</option>
                    @foreach ($unlinkedResidents as $resident)
                        <option value="{{ $resident->id }}">{{ $resident->full_name }} · {{ $resident->household->household_code }}</option>
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
        <button class="mt-4 rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white" type="submit">Buat akun</button>
    </form>
    @if ($unlinkedResidents->hasPages())
        <div class="mt-3 text-sm text-slate-600">Halaman pilihan data warga: {{ $unlinkedResidents->links() }}</div>
    @endif

    @if (auth()->user()->hasRole('super_admin'))
    <section class="mt-8">
        <h2 class="text-lg font-bold">Wilayah terdaftar</h2>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($rwAreas as $rw)
                <article class="rounded-2xl border border-slate-200 bg-white p-4">
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

    <section class="mt-8">
        <h2 class="text-lg font-bold">Akun aplikasi</h2>
        <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full min-w-[42rem] text-left text-sm">
                <thead class="bg-slate-100 text-slate-600"><tr><th class="p-3">Nama</th><th class="p-3">Nama pengguna</th><th class="p-3">Peran</th><th class="p-3">Cakupan</th><th class="p-3">Status</th><th class="p-3">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($users as $user)
                        <tr>
                            <td class="p-3">{{ $user->name }}</td>
                            <td class="p-3">{{ $user->username }}</td>
                            <td class="p-3">{{ $user->role }}</td>
                            <td class="p-3">{{ $user->rtArea?->name ?? $user->rwArea?->name ?? ($user->role === 'resident' ? 'Rumah tangga' : 'Semua wilayah') }}</td>
                            <td class="p-3">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td class="p-3">
                                @unless (auth()->user()->is($user))
                                    <form method="POST" action="{{ route('users.status', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                        <button class="font-semibold text-emerald-800 underline" type="submit">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                    <details class="mt-2">
                                        <summary class="cursor-pointer font-semibold text-emerald-800">Ubah nama atau reset kata sandi</summary>
                                        <form class="mt-2 space-y-2" method="POST" action="{{ route('users.update', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <label class="block">Nama
                                                <input class="w-full rounded-lg border border-slate-300 px-2 py-1" name="name" value="{{ $user->name }}" maxlength="160" required>
                                            </label>
                                            <button class="font-semibold text-emerald-800 underline" type="submit">Simpan nama</button>
                                        </form>
                                        <form class="mt-3 space-y-2" method="POST" action="{{ route('users.password', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <label class="block">Kata sandi sementara (min. 12 karakter)
                                                <input class="w-full rounded-lg border border-slate-300 px-2 py-1" type="password" name="password" minlength="12" maxlength="255" autocomplete="new-password" required>
                                            </label>
                                            <label class="block">Ulangi kata sandi
                                                <input class="w-full rounded-lg border border-slate-300 px-2 py-1" type="password" name="password_confirmation" minlength="12" maxlength="255" autocomplete="new-password" required>
                                            </label>
                                            <button class="font-semibold text-rose-800 underline" type="submit">Reset kata sandi</button>
                                        </form>
                                    </details>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
    </section>
@endsection
