@extends('layouts.app')

@section('title', 'Area Privat')

@section('content')
    <section>
        <p class="text-sm font-semibold text-emerald-800">{{ $roleLabel }}</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">Selamat datang, {{ auth()->user()->name }}</h1>
        <p class="mt-2 text-slate-600">Informasi pada halaman ini dibatasi sesuai akun dan wilayah yang ditetapkan pengurus.</p>
    </section>

    <section class="mt-8 grid gap-4 sm:grid-cols-2" aria-label="Ringkasan akun">
        @foreach ($stats as $label => $value)
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-3xl font-bold">{{ number_format($value, 0, ',', '.') }}</p>
            </article>
        @endforeach
        @if ($householdCode)
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">Kode rumah tangga</p>
                <p class="mt-2 text-xl font-bold">{{ $householdCode }}</p>
            </article>
        @endif
    </section>

    <section class="mt-8 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-sm leading-6 text-emerald-950">
        Gunakan menu di bagian atas untuk membuka layanan, keuangan, dan inventaris. Data privat hanya tersedia setelah pemeriksaan hak akses server; dokumen tetap nonaktif sampai B2 dikonfigurasi.
    </section>
@endsection
