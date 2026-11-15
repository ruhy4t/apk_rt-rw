@extends('layouts.app')

@section('title', 'Profil')

@section('content')
    <h1 class="text-2xl font-bold">Profil</h1>
    <p class="mt-2 text-sm text-slate-600">Nama pengguna: {{ $user->username }} (tidak dapat diubah)</p>
    @unless ($user->must_change_password)
        <form class="mt-6 rounded-2xl border border-slate-200 bg-white p-5" method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')
            <label class="block text-sm font-medium">Nama
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="name" value="{{ old('name', $user->name) }}" maxlength="160" required>
            </label>
            <button class="mt-4 rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white" type="submit">Simpan profil</button>
        </form>
    @endunless
    <form class="mt-6 rounded-2xl border border-slate-200 bg-white p-5" method="POST" action="{{ route('profile.password') }}">
        @csrf
        @method('PATCH')
        <h2 class="font-bold">Ganti kata sandi</h2>
        @if ($user->must_change_password)
            <p class="mt-2 text-sm text-rose-700">Anda harus mengganti kata sandi sementara sebelum menggunakan aplikasi.</p>
        @endif
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <label class="text-sm font-medium">Kata sandi saat ini
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" type="password" name="current_password" autocomplete="current-password" maxlength="255" required>
            </label>
            <label class="text-sm font-medium">Kata sandi baru (min. 12 karakter)
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" type="password" name="password" autocomplete="new-password" minlength="12" maxlength="255" required>
            </label>
            <label class="text-sm font-medium">Ulangi kata sandi baru
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="255" required>
            </label>
        </div>
        <button class="mt-4 rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white" type="submit">Ganti kata sandi</button>
    </form>
@endsection
