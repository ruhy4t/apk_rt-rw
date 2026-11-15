@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <section class="mx-auto max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-sm font-semibold text-emerald-800">Area privat</p>
        <h1 class="mt-2 text-2xl font-bold">Masuk ke akun warga</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Akun dibuat oleh pengurus. Pendaftaran mandiri tidak tersedia.</p>

        <form class="mt-7 space-y-5" method="POST" action="{{ route('login.store') }}">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-medium" for="username">Nama pengguna</label>
                <input class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-100" id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus maxlength="80">
                @error('username')
                    <p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium" for="password">Kata sandi</label>
                <input class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-100" id="password" name="password" type="password" autocomplete="current-password" required>
                @error('password')
                    <p class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>
            <button class="w-full rounded-xl bg-emerald-700 px-4 py-3 font-semibold text-white hover:bg-emerald-800" type="submit">Masuk</button>
        </form>
    </section>
@endsection
