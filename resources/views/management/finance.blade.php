@extends('layouts.app')

@section('title', 'Keuangan')

@section('content')
    <div class="page-heading"><div><h1>Keuangan dan iuran</h1>
    <p class="mt-2 text-sm text-slate-600">
        @if ($isResident)
            Riwayat yang ditampilkan hanya iuran rumah tangga Anda.
        @else
            Detail keuangan hanya terlihat oleh petugas dengan cakupan wilayah yang sesuai.
        @endif
    </p></div>
        @unless ($isResident)<button class="button button-primary" type="button" data-open-dialog="create-finance"><x-icon name="plus" /><span data-button-label>Catat transaksi / iuran</span></button>@endunless
    </div>

    @unless ($isResident)
        <dialog id="create-finance" aria-labelledby="create-finance-title" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-4xl overflow-y-auto rounded-lg border border-slate-200 bg-white p-5 sm:p-7" @if ($errors->any() && old('_form') === 'create-finance') data-auto-open @endif>
            <div class="flex items-start justify-between gap-4">
                <h2 id="create-finance-title" class="text-xl font-bold"><x-icon name="plus" />Catat transaksi atau pembayaran iuran</h2>
                <button class="button" type="button" data-close-dialog><x-icon name="close" /><span data-button-label>Tutup</span></button>
            </div>
        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <form class="rounded-lg border border-slate-200 bg-white p-5" method="POST" action="{{ route('finance.entries.store') }}">
                @csrf
                <input type="hidden" name="_form" value="create-finance">
                <h2 class="font-bold"><x-icon name="wallet" />Catat transaksi kas</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="text-sm font-medium">Wilayah
                        <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="rt_area_id" required>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }} · {{ $area->code }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium">Jenis
                        <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="direction" required>
                            <option value="income">Penerimaan</option>
                            <option value="expense">Pengeluaran</option>
                        </select>
                    </label>
                    <label class="text-sm font-medium">Kategori
                        <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="category" maxlength="80" required>
                    </label>
                    <label class="text-sm font-medium">Jumlah (Rp)
                        <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="amount" type="number" min="1" max="1000000000000" required>
                    </label>
                    <label class="text-sm font-medium sm:col-span-2">Tanggal
                        <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="occurred_on" type="date" required>
                    </label>
                    <label class="text-sm font-medium sm:col-span-2">Keterangan (opsional)
                        <textarea class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="description" rows="2" maxlength="2000"></textarea>
                    </label>
                    <label class="flex items-start gap-2 text-sm sm:col-span-2">
                        <input class="mt-1" type="checkbox" name="public_summary" value="1">
                        <span>Tampilkan hanya dalam ringkasan agregat publik. Detail transaksi tidak ditampilkan.</span>
                    </label>
                </div>
                <button class="mt-4 rounded-lg bg-brand-700 px-4 py-2.5 font-semibold text-white" type="submit"><x-icon name="save" /><span data-button-label>Simpan transaksi</span></button>
            </form>

            <form class="rounded-lg border border-slate-200 bg-white p-5" method="POST" action="{{ route('finance.contributions.store') }}">
                @csrf
                <input type="hidden" name="_form" value="create-finance">
                <h2 class="font-bold"><x-icon name="wallet" />Catat pembayaran iuran</h2>
                <div class="mt-4 space-y-4">
                    <label class="block text-sm font-medium">Rumah tangga
                        <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="household_id" required>
                            @foreach ($households as $household)
                                <option value="{{ $household->id }}">{{ $household->household_code }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-sm font-medium">Periode
                            <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="period" type="month" required>
                        </label>
                        <label class="text-sm font-medium">Jumlah (Rp)
                            <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="amount" type="number" min="1" max="1000000000000" required>
                        </label>
                    </div>
                    <label class="block text-sm font-medium">Tanggal bayar (opsional)
                        <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="paid_at" type="date">
                    </label>
                    <label class="block text-sm font-medium">Catatan (opsional)
                        <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="note" maxlength="1000">
                    </label>
                </div>
                <button class="mt-4 rounded-lg bg-brand-700 px-4 py-2.5 font-semibold text-white" type="submit"><x-icon name="save" /><span data-button-label>Simpan iuran</span></button>
            </form>
        </div>
        </dialog>
    @endunless

    @unless ($isResident)
        <section class="mt-8">
            <h2 class="text-lg font-bold">Transaksi kas</h2>
            <div class="mt-3 overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table class="w-full min-w-[42rem] text-left text-sm">
                    <thead class="bg-slate-100 text-slate-600"><tr><th class="p-3">Tanggal</th><th class="p-3">Wilayah</th><th class="p-3">Jenis</th><th class="p-3">Kategori</th><th class="p-3 money">Jumlah</th><th class="p-3">Publik agregat</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($entries as $entry)
                            <tr>
                                <td class="p-3">{{ $entry->occurred_on->format('d-m-Y') }}</td>
                                <td class="p-3">{{ $entry->rtArea->name }}</td>
                                <td class="p-3">{{ $entry->direction === 'income' ? 'Penerimaan' : 'Pengeluaran' }}</td>
                                <td class="p-3">{{ $entry->category }}</td>
                                <td class="p-3 money font-semibold">{{ 'Rp '.number_format($entry->amount, 0, ',', '.') }}</td>
                                <td class="p-3">{{ $entry->public_summary ? 'Ya' : 'Tidak' }}</td>
                            </tr>
                        @empty
                            <tr><td class="p-4 text-slate-500" colspan="6">Belum ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $entries->links() }}</div>
        </section>
    @endunless

    <section class="mt-8">
        <h2 class="text-lg font-bold">Riwayat iuran</h2>
        <div class="mt-3 overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="w-full min-w-[36rem] text-left text-sm">
                <thead class="bg-slate-100 text-slate-600"><tr>@unless ($isResident)<th class="p-3">Rumah tangga</th>@endunless<th class="p-3">Periode</th><th class="p-3 money">Jumlah</th><th class="p-3">Tanggal bayar</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($contributions as $contribution)
                        <tr>
                            @unless ($isResident)<td class="p-3">{{ $contribution->household->household_code }}</td>@endunless
                            <td class="p-3">{{ $contribution->period }}</td>
                            <td class="p-3 money font-semibold">{{ 'Rp '.number_format($contribution->amount, 0, ',', '.') }}</td>
                            <td class="p-3">{{ $contribution->paid_at?->format('d-m-Y') ?? 'Belum tercatat' }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-4 text-slate-500" colspan="{{ $isResident ? 3 : 4 }}">Belum ada pembayaran iuran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $contributions->links() }}</div>
    </section>
@endsection
