@extends('layouts.app')

@section('title', 'Ubah data warga')

@section('content')
    <h1>Ubah data warga</h1>
    <p class="mt-2 text-sm text-slate-600">Rumah tangga: {{ $resident->household->household_code }}</p>
    <form class="mt-6 max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-5" method="POST" action="{{ route('residents.details', $resident) }}">
        @csrf
        @method('PATCH')
        @if ($resident->relationship !== 'Kepala Keluarga')
            <label class="block text-sm font-medium">Hubungan keluarga
                <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" name="relationship" required>
                    <option value="">Pilih hubungan</option>
                    @foreach (['Istri', 'Suami', 'Anak', 'Famili Lain'] as $relationship)
                        <option value="{{ $relationship }}" @selected(old('relationship', $resident->relationship) === $relationship)>{{ $relationship }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        @include('management.resident-fields', ['editingResident' => $resident])
        <button class="rounded-lg bg-brand-700 px-4 py-2.5 font-semibold text-white" type="submit"><x-icon name="save" /><span data-button-label>Simpan perubahan</span></button>
        <a class="ml-3 text-sm text-brand-800 underline" href="{{ route('residents.index') }}">Kembali ke data warga</a>
    </form>
@endsection
