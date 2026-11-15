@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <div class="login-layout">
        <section class="login-intro">
            <p class="eyebrow">Akun warga & pengurus</p>
            <h1>Layanan lingkungan, lewat akun Anda.</h1>
            <p>Gunakan akun dari pengurus untuk mengakses layanan warga, riwayat iuran, dan informasi rumah tangga Anda.</p>
            <p>Belum memiliki akun atau kesulitan masuk?<br>Hubungi pengurus RT/RW untuk bantuan.</p>
            <a class="text-link mt-6 inline-block" href="{{ route('home') }}"><x-icon name="arrow-left" />Kembali ke informasi publik</a>
        </section>
        <section class="panel">
            <h2 class="text-xl">Masuk ke akun warga</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Akun dibuat oleh pengurus. Pendaftaran mandiri tidak tersedia.</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">Lima percobaan masuk yang gagal akan mengunci akses masuk akun selama 5 menit.</p>
            <form class="mt-6 space-y-5" method="POST" action="{{ route('login.store') }}">
                @csrf
                <div>
                    <label class="mb-1.5 block text-sm font-medium" for="username">Nama pengguna</label>
                    <input class="w-full rounded-md border border-slate-300 px-3 py-2.5" id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus maxlength="80" @error('username') aria-invalid="true" aria-describedby="username-error" @enderror>
                    @error('username')<p id="username-error" class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium" for="password">Kata sandi</label>
                    <input class="w-full rounded-md border border-slate-300 px-3 py-2.5" id="password" name="password" type="password" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                    @error('password')<p id="password-error" class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium" for="captcha">Jawab penjumlahan: {{ $captchaQuestion }}</label>
                    <input class="w-full rounded-md border border-slate-300 px-3 py-2.5" id="captcha" name="captcha" type="text" inputmode="numeric" pattern="[0-9]{1,2}" maxlength="2" autocomplete="off" required aria-describedby="captcha-help @error('captcha') captcha-error @enderror" @error('captcha') aria-invalid="true" @enderror>
                    <p id="captcha-help" class="mt-1.5 text-sm text-slate-600">Soal berlaku 10 menit dan hanya bisa dipakai sekali. Muat ulang halaman untuk mengganti soal.</p>
                    @error('captcha')<p id="captcha-error" class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <button class="button button-primary w-full" type="submit"><x-icon name="login" /><span data-button-label>Masuk</span></button>
            </form>
        </section>
    </div>
@endsection
