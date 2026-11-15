@extends('layouts.app')

@section('title', 'Informasi Warga')

@section('content')
    <section class="public-intro">
        <div>
            <p class="eyebrow">Portal warga RT / RW</p>
            <h1>Informasi lingkungan.<br>Layanan untuk warga.</h1>
            <p class="intro-copy">Pengumuman, kegiatan, dan informasi lingkungan dalam satu tempat. Temukan kabar terbaru dari pengurus di sini.</p>
            <a class="text-link mt-5 inline-block" href="#pengumuman">Lihat pengumuman <span aria-hidden="true">↓</span></a>
        </div>
        <aside>
            <h2><x-icon name="document" />Layanan warga</h2>
            <p>Ajukan layanan, lihat riwayat iuran, dan pinjam inventaris melalui akun Anda.</p>
            @auth
                <a class="button button-primary" href="{{ route('dashboard') }}">Buka area warga <x-icon name="arrow-right" class="no-icon-gap" /></a>
            @else
                <a class="button button-primary" href="{{ route('login') }}">Masuk ke akun <x-icon name="arrow-right" class="no-icon-gap" /></a>
                <p>Akun diberikan oleh pengurus RT/RW.</p>
            @endauth
        </aside>
    </section>

    <div class="public-information">
        <section id="pengumuman">
            <div class="section-heading"><h2><x-icon name="announcement" />Pengumuman</h2><span>Informasi publik</span></div>
            <div class="editorial-list">
                @forelse ($announcements as $announcement)
                    <article>
                        <time @if ($announcement->published_at) datetime="{{ $announcement->published_at->toDateString() }}" @endif>{{ $announcement->published_at?->translatedFormat('d F Y') ?? 'Pengumuman' }}</time>
                        <h3>{{ $announcement->title }}</h3>
                        <p>{{ $announcement->body }}</p>
                    </article>
                @empty
                    <p class="empty-state mt-4">Belum ada pengumuman publik.</p>
                @endforelse
            </div>
        </section>
        <section id="agenda">
            <div class="section-heading"><h2><x-icon name="calendar" />Agenda mendatang</h2><span>Kegiatan lingkungan</span></div>
            <div class="editorial-list">
                @forelse ($events as $event)
                    <article class="event-row">
                        <div class="event-date" aria-hidden="true"><strong>{{ $event->starts_at->format('d') }}</strong><span>{{ $event->starts_at->translatedFormat('M') }}</span></div>
                        <div>
                            <time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->starts_at->translatedFormat('l, d F Y · H:i') }}</time>
                            <h3>{{ $event->title }}</h3>
                            @if ($event->description)<p>{{ $event->description }}</p>@endif
                        </div>
                    </article>
                @empty
                    <p class="empty-state mt-4">Belum ada agenda publik yang akan datang.</p>
                @endforelse
            </div>
        </section>
    </div>
    <section id="ringkasan" class="mt-10">
        <div class="section-heading"><h2><x-icon name="wallet" />Ringkasan lingkungan</h2><span>Data yang dipublikasikan pengurus</span></div>
        <dl class="stats-strip">
            <div><dt>Penerimaan tercatat</dt><dd>{{ 'Rp '.number_format($incomeTotal, 0, ',', '.') }}</dd></div>
            <div><dt>Pengeluaran tercatat</dt><dd>{{ 'Rp '.number_format($expenseTotal, 0, ',', '.') }}</dd></div>
            <div><dt>Inventaris lingkungan</dt><dd>{{ number_format($assetQuantity, 0, ',', '.') }} <span class="text-sm font-normal">unit</span></dd></div>
        </dl>
        <p class="mt-3 text-xs text-slate-500">Hanya ringkasan agregat yang dipublikasikan. Detail transaksi dan data warga tidak ditampilkan.</p>
    </section>
@endsection
