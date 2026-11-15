@extends('layouts.app')

@section('title', 'Pengaturan pengurus')

@section('content')
    <h1 class="text-2xl font-bold">Pengaturan wilayah dan akun</h1>
    <p class="mt-2 text-sm text-slate-600">Buat akun untuk warga/petugas dan berikan hanya cakupan wilayah yang diperlukan. Kata sandi tidak dikirim melalui tautan atau URL.</p>

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

    <form class="mt-6 rounded-2xl border border-slate-200 bg-white p-5" method="POST" action="{{ route('admin.users.store') }}">
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
                    <option value="rt_operator">Petugas RT</option>
                    <option value="rw_operator">Petugas RW</option>
                </select>
            </label>
            <label class="text-sm font-medium">Wilayah RW (untuk petugas RW)
                <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="rw_area_id">
                    <option value="">Tidak ditetapkan</option>
                    @foreach ($rwAreas as $rw)
                        <option value="{{ $rw->id }}">{{ $rw->name }} · {{ $rw->code }}</option>
                    @endforeach
                </select>
            </label>
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
                                    <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                        <button class="font-semibold text-emerald-800 underline" type="submit">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
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
