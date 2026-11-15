@extends('layouts.app')

@section('title', 'Data Warga')

@section('content')
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section>
            <h1 class="text-2xl font-bold">Data rumah tangga dan warga</h1>
            <p class="mt-2 text-sm text-slate-600">Data ini hanya dapat dilihat petugas sesuai cakupan RT/RW yang ditugaskan.</p>

            <div class="mt-6 space-y-4">
                @forelse ($households as $household)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="font-semibold">{{ $household->household_code }}</h2>
                                <p class="text-sm text-slate-500">{{ $household->rtArea->name }} · {{ $household->rtArea->code }}</p>
                                @if ($household->address)
                                    <p class="mt-1 text-sm text-slate-600">{{ $household->address }}</p>
                                @endif
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">{{ $household->residents->count() }} warga</span>
                        </div>
                        <div class="mt-4 divide-y divide-slate-100">
                            @forelse ($household->residents as $resident)
                                <div class="py-3">
                                    <p class="font-medium">{{ $resident->full_name }}</p>
                                    <p class="text-sm text-slate-500">{{ $resident->relationship ?: 'Anggota rumah tangga' }}@if ($resident->phone) · {{ $resident->phone }}@endif</p>
                                </div>
                            @empty
                                <p class="py-3 text-sm text-slate-500">Belum ada data anggota.</p>
                            @endforelse
                        </div>
                        <form class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-3" method="POST" action="{{ route('residents.people.store', $household) }}">
                            @csrf
                            <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="full_name" placeholder="Nama lengkap" maxlength="160" required>
                            <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="relationship" placeholder="Hubungan keluarga" maxlength="50">
                            <div class="flex gap-2">
                                <input class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm" name="phone" placeholder="Nomor telepon (opsional)" maxlength="32">
                                <button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white" type="submit">Tambah</button>
                            </div>
                        </form>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-500">Belum ada rumah tangga pada wilayah yang ditugaskan.</p>
                @endforelse
            </div>
            <div class="mt-5">{{ $households->links() }}</div>
        </section>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="font-bold">Tambah rumah tangga</h2>
            @if ($areas->isEmpty())
                <p class="mt-3 text-sm text-slate-600">Belum ada wilayah RT yang dapat dikelola. Hubungi administrator untuk penetapan wilayah.</p>
            @else
                <form class="mt-4 space-y-4" method="POST" action="{{ route('households.store') }}">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="rt_area_id">Wilayah RT</label>
                        <select class="w-full rounded-lg border border-slate-300 px-3 py-2" id="rt_area_id" name="rt_area_id" required>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }} · {{ $area->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="household_code">Kode rumah tangga</label>
                        <input class="w-full rounded-lg border border-slate-300 px-3 py-2" id="household_code" name="household_code" maxlength="32" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="address">Alamat (opsional)</label>
                        <textarea class="w-full rounded-lg border border-slate-300 px-3 py-2" id="address" name="address" rows="3" maxlength="2000"></textarea>
                    </div>
                    <button class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white" type="submit">Simpan rumah tangga</button>
                </form>
            @endif
        </aside>
    </div>
@endsection
