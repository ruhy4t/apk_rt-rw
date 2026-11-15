@extends('layouts.app')

@section('title', 'Data Warga')

@section('content')
    <div>
        <section>
            <div class="page-heading">
                <div><h1>Data rumah tangga dan warga</h1><p>Kelola data sesuai cakupan RT/RW yang ditugaskan.</p></div>
                <button class="button button-primary" type="button" data-open-dialog="create-household"><x-icon name="plus" /><span data-button-label>Tambah rumah tangga</span></button>
            </div>
            <noscript><p class="mt-3 text-sm text-rose-700">Aktifkan JavaScript untuk membuka formulir rumah tangga.</p></noscript>

            <div class="mt-6 space-y-4">
                @forelse ($households as $household)
                    <article class="rounded-lg border border-slate-200 bg-white p-5">
                        <div class="household-summary">
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
                                <div class="member-row">
                                    <div><p class="font-semibold">{{ $resident->full_name }}</p><p class="text-slate-500">{{ $resident->relationship ?: 'Anggota rumah tangga' }}@if ($resident->phone) · {{ $resident->phone }}@endif</p></div>
                                    <a class="text-link shrink-0" href="{{ route('residents.edit', $resident) }}"><x-icon name="edit" />Ubah data</a>
                                </div>
                            @empty
                                <p class="py-3 text-sm text-slate-500">Belum ada data anggota.</p>
                            @endforelse
                        </div>
                        <a class="mt-4 inline-block font-semibold text-brand-800 underline" href="{{ route('residents.people.create', $household) }}"><x-icon name="plus" />Tambah anggota keluarga</a>
                        <details class="mt-3 text-sm">
                            <summary class="cursor-pointer font-semibold text-brand-800"><x-icon name="edit" />Perbarui nomor KK</summary>
                            <form class="mt-3 flex flex-wrap items-end gap-2" method="POST" action="{{ route('households.identity', $household) }}">
                                @csrf
                                @method('PATCH')
                                <label class="font-medium">Nomor KK (16 digit)
                                    <input class="mt-1 block rounded-lg border border-slate-300 px-3 py-2 font-normal" name="family_card_number" inputmode="numeric" pattern="[0-9]{16}" minlength="16" maxlength="16" autocomplete="off" required>
                                </label>
                                <button class="rounded-lg bg-brand-700 px-3 py-2 font-semibold text-white" type="submit"><x-icon name="save" /><span data-button-label>Simpan</span></button>
                            </form>
                        </details>
                    </article>
                @empty
                    <div class="empty-state"><p class="font-semibold text-slate-700">Belum ada rumah tangga pada wilayah yang ditugaskan.</p><p class="mt-1">Mulai dengan menambahkan rumah tangga dan data kepala keluarganya.</p></div>
                @endforelse
            </div>
            <div class="mt-5">{{ $households->links() }}</div>
        </section>

        <dialog id="create-household" aria-labelledby="create-household-title" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-3xl overflow-y-auto rounded-lg border border-slate-200 bg-white p-5 sm:p-7" @if ($errors->any() && old('_form') === 'create-household') data-auto-open @endif>
            <div class="flex items-center justify-between gap-4">
                <div><h2 id="create-household-title" class="text-xl font-bold">Tambah rumah tangga</h2><p class="mt-1 text-sm text-slate-500">Isi data rumah tangga dan kepala keluarga. Semua isian wajib kecuali ditandai opsional.</p></div>
                <button class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100" type="button" data-close-dialog><x-icon name="close" /><span data-button-label>Tutup</span></button>
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
                    <fieldset class="form-section"><legend>01 · Rumah tangga</legend><div class="form-grid">
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
                    </div></fieldset>
                    <fieldset class="form-section"><legend>02 · Data kepala keluarga</legend>
                    @include('management.resident-fields', ['editingResident' => null])
                    </fieldset>
                    <div class="form-actions"><button class="button button-primary" type="submit"><x-icon name="save" /><span data-button-label>Simpan rumah tangga</span></button><button class="button" type="button" data-close-dialog><x-icon name="close" /><span data-button-label>Batal</span></button></div>
                </form>
            @endif
        </dialog>
    </div>
@endsection
