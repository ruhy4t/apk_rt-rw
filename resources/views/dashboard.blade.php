@extends('layouts.app')

@section('title', 'Area warga')

@section('content')
    <section class="welcome-heading">
        <p class="eyebrow">{{ $roleLabel }}</p>
        <h1>Selamat datang, {{ auth()->user()->name }}</h1>
        <p>{{ auth()->user()->hasRole('resident') ? 'Akses layanan dan informasi untuk rumah tangga Anda.' : 'Kelola informasi dan administrasi wilayah yang ditugaskan kepada Anda.' }}</p>
    </section>

    <section class="mt-8" aria-labelledby="tasks-title">
        <div class="section-heading"><h2 id="tasks-title">{{ auth()->user()->hasRole('resident') ? 'Keperluan Anda' : 'Mulai pekerjaan' }}</h2><span>Akses langsung</span></div>
        <div class="task-grid">
            <a class="task-link" href="{{ route('requests.index') }}">
                <strong><span class="action-label"><x-icon name="document" />Layanan warga</span> <x-icon name="arrow-right" class="no-icon-gap" /></strong>
                <p>{{ auth()->user()->hasRole('resident') ? 'Ajukan surat keterangan, sampaikan aduan, dan lihat tanggapan pengurus.' : 'Tinjau permintaan warga dan berikan tanggapan atau pembaruan status.' }}</p>
            </a>
            @if (auth()->user()->hasRole('resident'))
                <a class="task-link" href="{{ route('households.mine') }}"><strong><span class="action-label"><x-icon name="household" />Keluarga saya</span> <x-icon name="arrow-right" class="no-icon-gap" /></strong><p>Lihat anggota keluarga dan data rumah tangga yang terhubung ke akun Anda.</p></a>
            @else
                <a class="task-link" href="{{ route('residents.index') }}"><strong><span class="action-label"><x-icon name="users" />Data warga</span> <x-icon name="arrow-right" class="no-icon-gap" /></strong><p>Kelola rumah tangga dan perbarui data anggota keluarga sesuai wilayah.</p></a>
            @endif
            <a class="task-link" href="{{ route('finance.index') }}"><strong><span class="action-label"><x-icon name="wallet" />{{ auth()->user()->hasRole('resident') ? 'Riwayat iuran' : 'Keuangan & iuran' }}</span> <x-icon name="arrow-right" class="no-icon-gap" /></strong><p>{{ auth()->user()->hasRole('resident') ? 'Periksa pembayaran iuran yang sudah dicatat untuk rumah tangga Anda.' : 'Catat transaksi kas dan pembayaran iuran rumah tangga.' }}</p></a>
        </div>
    </section>

    <section class="mt-8" aria-labelledby="summary-title">
        <div class="section-heading"><h2 id="summary-title">{{ auth()->user()->hasRole('resident') ? 'Ringkasan akun' : 'Ringkasan wilayah' }}</h2><span>Sesuai cakupan akses</span></div>
        <dl class="stats-strip">
            @foreach ($stats as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ number_format($value, 0, ',', '.') }}</dd></div>
            @endforeach
            @if ($householdCode)
                <div><dt>Kode rumah tangga</dt><dd class="text-xl">{{ $householdCode }}</dd></div>
            @endif
        </dl>
    </section>
    <section class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 pt-6">
        <div><h2><x-icon name="inventory" />Inventaris lingkungan</h2><p class="mt-1 text-sm text-slate-600">{{ auth()->user()->hasRole('resident') ? 'Lihat barang yang tersedia dan ajukan peminjaman.' : 'Kelola barang dan permintaan peminjaman warga.' }}</p></div>
        <a class="button" href="{{ route('assets.index') }}">Buka inventaris <x-icon name="arrow-right" class="no-icon-gap" /></a>
    </section>
@endsection
