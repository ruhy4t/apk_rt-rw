@extends('layouts.app')

@section('title', 'Keluarga saya')

@section('content')
    <h1 class="text-2xl font-bold">Keluarga saya</h1>
    @if (! $household)
        <p class="mt-4 rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600">Akun Anda belum terhubung ke data rumah tangga. Hubungi pengurus RT.</p>
    @else
        <p class="mt-2 text-sm text-slate-600">Kode rumah tangga: {{ $household->household_code }}</p>
        <section class="mt-6 space-y-3" aria-label="Anggota keluarga">
            @foreach ($household->residents as $resident)
                <article class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="font-semibold">{{ $resident->full_name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $resident->relationship ?: 'Anggota keluarga' }}</p>
                    <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                        <div><dt class="text-slate-500">Jenis kelamin</dt><dd>{{ $resident->gender ?: 'Belum diisi' }}</dd></div>
                        <div><dt class="text-slate-500">Tanggal lahir</dt><dd>{{ $resident->birth_date?->format('d-m-Y') ?: 'Belum diisi' }}</dd></div>
                        <div><dt class="text-slate-500">Pendidikan</dt><dd>{{ $resident->education ?: 'Belum diisi' }}</dd></div>
                        <div><dt class="text-slate-500">Pekerjaan</dt><dd>{{ $resident->occupation ?: 'Belum diisi' }}</dd></div>
                    </dl>
                </article>
            @endforeach
        </section>
    @endif
@endsection
