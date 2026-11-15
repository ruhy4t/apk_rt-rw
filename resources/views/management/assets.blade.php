@extends('layouts.app')

@section('title', 'Inventaris')

@section('content')
    <h1 class="text-2xl font-bold">Inventaris dan peminjaman</h1>
    <p class="mt-2 text-sm text-slate-600">Permintaan peminjaman hanya dapat dibuat untuk aset pada wilayah rumah tangga Anda.</p>

    @unless ($isResident)
        <form class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-2 lg:grid-cols-3" method="POST" action="{{ route('assets.store') }}">
            @csrf
            <h2 class="font-bold sm:col-span-2 lg:col-span-3">Tambah inventaris</h2>
            <label class="text-sm font-medium">Wilayah
                <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="rt_area_id" required>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}">{{ $area->name }} · {{ $area->code }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium">Nama barang
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="name" maxlength="160" required>
            </label>
            <label class="text-sm font-medium">Kategori
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="category" maxlength="80">
            </label>
            <label class="text-sm font-medium">Jumlah
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="quantity" type="number" min="1" max="100000" value="1" required>
            </label>
            <label class="text-sm font-medium">Kondisi
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="condition" maxlength="32">
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="public_summary" value="1">
                Jumlah agregat boleh tampil publik
            </label>
            <div class="sm:col-span-2 lg:col-span-3"><button class="rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white" type="submit">Simpan inventaris</button></div>
        </form>
    @endunless

    <section class="mt-8">
        <h2 class="text-lg font-bold">Daftar inventaris</h2>
        <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($assets as $asset)
                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h3 class="font-semibold">{{ $asset->name }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $asset->category ?: 'Tanpa kategori' }} · {{ $asset->condition ?: 'Kondisi belum dicatat' }}</p>
                    <p class="mt-3 text-sm">Tersedia: {{ $asset->quantity }} unit</p>
                    @if ($isResident)
                        <form class="mt-4 space-y-3 border-t border-slate-100 pt-4" method="POST" action="{{ route('assets.loans.store', $asset) }}">
                            @csrf
                            <label class="block text-sm font-medium">Jumlah
                                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="quantity" type="number" min="1" max="1000" value="1" required>
                            </label>
                            <label class="block text-sm font-medium">Keperluan
                                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="purpose" maxlength="1000">
                            </label>
                            <label class="block text-sm font-medium">Tanggal mulai
                                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="starts_at" type="datetime-local">
                            </label>
                            <label class="block text-sm font-medium">Batas pengembalian
                                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="due_at" type="datetime-local">
                            </label>
                            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white" type="submit">Ajukan pinjam</button>
                        </form>
                    @endif
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-500">Belum ada inventaris pada wilayah yang dapat diakses.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $assets->links() }}</div>
    </section>

    <section class="mt-8">
        <h2 class="text-lg font-bold">Permintaan peminjaman</h2>
        <div class="mt-3 space-y-3">
            @forelse ($loans as $loan)
                <article class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4">
                    <div>
                        <p class="font-semibold">{{ $loan->asset->name }} · {{ $loan->quantity }} unit</p>
                        <p class="text-sm text-slate-500">{{ $loan->household->household_code }} · {{ $loan->status }}</p>
                        @if ($loan->purpose)<p class="mt-1 text-sm text-slate-600">{{ $loan->purpose }}</p>@endif
                    </div>
                    @unless ($isResident)
                        @if ($loan->status === 'pending' || $loan->status === 'approved')
                            <form class="flex flex-wrap gap-2" method="POST" action="{{ route('assets.loans.update', $loan) }}">
                                @csrf
                                @method('PATCH')
                                @if ($loan->status === 'pending')
                                    <button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white" name="status" value="approved" type="submit">Setujui</button>
                                    <button class="rounded-lg border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700" name="status" value="rejected" type="submit">Tolak</button>
                                @else
                                    <button class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold" name="status" value="returned" type="submit">Tandai kembali</button>
                                @endif
                            </form>
                        @endif
                    @endunless
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-500">Belum ada permintaan peminjaman.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $loans->links() }}</div>
    </section>
@endsection
