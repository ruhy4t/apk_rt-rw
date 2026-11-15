@extends('layouts.app')

@section('title', 'Data Warga')

@section('content')
    <div>
        <section>
            <h1 class="text-2xl font-bold">Data rumah tangga dan warga</h1>
            <p class="mt-2 text-sm text-slate-600">Data ini hanya dapat dilihat petugas sesuai cakupan RT/RW yang ditugaskan.</p>
            <button class="mt-4 rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white hover:bg-emerald-800" type="button" data-open-dialog="create-household">Tambah rumah tangga</button>
            <noscript><p class="mt-3 text-sm text-rose-700">Aktifkan JavaScript untuk membuka formulir rumah tangga.</p></noscript>

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
                                    <a class="text-sm font-medium text-emerald-800 underline" href="{{ route('residents.edit', $resident) }}">Ubah data</a>
                                </div>
                            @empty
                                <p class="py-3 text-sm text-slate-500">Belum ada data anggota.</p>
                            @endforelse
                        </div>
                        <a class="mt-4 inline-block font-semibold text-emerald-800 underline" href="{{ route('residents.people.create', $household) }}">Tambah anggota keluarga</a>
                        <details class="mt-3 text-sm">
                            <summary class="cursor-pointer font-semibold text-emerald-800">Perbarui nomor KK</summary>
                            <form class="mt-3 flex flex-wrap items-end gap-2" method="POST" action="{{ route('households.identity', $household) }}">
                                @csrf
                                @method('PATCH')
                                <label class="font-medium">Nomor KK (16 digit)
                                    <input class="mt-1 block rounded-lg border border-slate-300 px-3 py-2 font-normal" name="family_card_number" inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" autocomplete="off" required>
                                </label>
                                <button class="rounded-lg bg-emerald-700 px-3 py-2 font-semibold text-white" type="submit">Simpan</button>
                            </form>
                        </details>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-500">Belum ada rumah tangga pada wilayah yang ditugaskan.</p>
                @endforelse
            </div>
            <div class="mt-5">{{ $households->links() }}</div>
        </section>

        <dialog id="create-household" aria-labelledby="create-household-title" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-xl backdrop:bg-slate-950/50" @if ($errors->any() && old('_form') === 'create-household') data-auto-open @endif>
            <div class="flex items-center justify-between gap-4">
                <h2 id="create-household-title" class="text-xl font-bold">Tambah rumah tangga</h2>
                <button class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" type="button" data-close-dialog>Tutup</button>
            </div>
            @if ($areas->isEmpty())
                <p class="mt-3 text-sm text-slate-600">Belum ada wilayah RT yang dapat dikelola. Hubungi administrator untuk penetapan wilayah.</p>
            @else
                @if ($errors->any() && old('_form') === 'create-household')
                    <div role="alert" class="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-900">
                        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif
                <form class="mt-4 space-y-4" method="POST" action="{{ route('households.store') }}">
                    @csrf
                    <input type="hidden" name="_form" value="create-household">
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="rt_area_id">Wilayah RT</label>
                        <select class="w-full rounded-lg border border-slate-300 px-3 py-2" id="rt_area_id" name="rt_area_id" required>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" @selected((string) old('rt_area_id') === (string) $area->id)>{{ $area->name }} · {{ $area->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="family_card_number">Nomor KK (16 digit)</label>
                        <input class="w-full rounded-lg border border-slate-300 px-3 py-2" id="family_card_number" name="family_card_number" inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" autocomplete="off" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="household_code">Kode rumah tangga</label>
                        <input class="w-full rounded-lg border border-slate-300 px-3 py-2" id="household_code" name="household_code" maxlength="32" value="{{ old('household_code') }}" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="address">Alamat (opsional)</label>
                        <textarea class="w-full rounded-lg border border-slate-300 px-3 py-2" id="address" name="address" rows="3" maxlength="2000">{{ old('address') }}</textarea>
                    </div>
                    <h3 class="border-t border-slate-100 pt-4 font-semibold">Data kepala keluarga</h3>
                    @include('management.resident-fields', ['editingResident' => null])
                    <button class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white" type="submit">Simpan rumah tangga</button>
                </form>
            @endif
        </dialog>
    </div>
@endsection
