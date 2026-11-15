@extends('layouts.app')

@section('title', 'Informasi Warga')

@section('content')
    <section class="rounded-3xl bg-emerald-900 px-6 py-10 text-white sm:px-10 sm:py-14">
        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-emerald-200">Portal warga</p>
        <h1 class="mt-3 max-w-2xl text-3xl font-bold tracking-tight sm:text-5xl">Informasi lingkungan, lebih mudah diakses.</h1>
        <p class="mt-4 max-w-xl text-base leading-7 text-emerald-100">Lihat pengumuman dan agenda lingkungan. Layanan khusus warga tersedia setelah masuk.</p>
        @guest
            <a class="mt-7 inline-flex rounded-xl bg-white px-5 py-3 font-semibold text-emerald-900 hover:bg-emerald-50" href="{{ route('login') }}">Masuk ke layanan warga</a>
        @endguest
    </section>

    <section class="mt-8 grid gap-4 sm:grid-cols-3" aria-label="Ringkasan publik">
        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Penerimaan tercatat</p>
            <p class="mt-2 text-2xl font-bold">{{ 'Rp '.number_format($incomeTotal, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">Ringkasan agregat yang dipublikasikan pengurus</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Pengeluaran tercatat</p>
            <p class="mt-2 text-2xl font-bold">{{ 'Rp '.number_format($expenseTotal, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">Ringkasan agregat yang dipublikasikan pengurus</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Inventaris lingkungan</p>
            <p class="mt-2 text-2xl font-bold">{{ number_format($assetQuantity, 0, ',', '.') }} unit</p>
            <p class="mt-1 text-xs text-slate-500">Jumlah agregat inventaris yang dipublikasikan</p>
        </article>
    </section>

    <div class="mt-10 grid gap-8 lg:grid-cols-2">
        <section>
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold">Pengumuman</h2>
                <span class="text-sm text-slate-500">Informasi publik</span>
            </div>
            <div class="space-y-3">
                @forelse ($announcements as $announcement)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="text-xs text-slate-500">{{ $announcement->published_at?->translatedFormat('d F Y') ?? 'Pengumuman' }}</p>
                        <h3 class="mt-2 font-semibold">{{ $announcement->title }}</h3>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $announcement->body }}</p>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-500">Belum ada pengumuman publik.</p>
                @endforelse
            </div>
        </section>

        <section>
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold">Agenda mendatang</h2>
                <span class="text-sm text-slate-500">Kegiatan lingkungan</span>
            </div>
            <div class="space-y-3">
                @forelse ($events as $event)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5">
                        <p class="text-xs font-semibold text-emerald-800">{{ $event->starts_at->translatedFormat('l, d F Y · H:i') }}</p>
                        <h3 class="mt-2 font-semibold">{{ $event->title }}</h3>
                        @if ($event->description)
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $event->description }}</p>
                        @endif
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-500">Belum ada agenda publik yang akan datang.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
