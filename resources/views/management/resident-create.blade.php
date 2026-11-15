@extends('layouts.app')

@section('title', 'Tambah anggota keluarga')

@section('content')
    <h1 class="text-2xl font-bold">Tambah anggota keluarga</h1>
    <p class="mt-2 text-sm text-slate-600">Rumah tangga: {{ $household->household_code }}</p>
    <form class="mt-6 max-w-2xl space-y-4 rounded-2xl border border-slate-200 bg-white p-5" method="POST" action="{{ route('residents.people.store', $household) }}">
        @csrf
        <label class="block text-sm font-medium">Hubungan keluarga
            <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" name="relationship" required>
                <option value="">Pilih hubungan</option>
                @foreach (['Istri', 'Suami', 'Anak', 'Famili Lain'] as $relationship)
                    <option value="{{ $relationship }}">{{ $relationship }}</option>
                @endforeach
            </select>
        </label>
        @include('management.resident-fields', ['editingResident' => null])
        <button class="rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white" type="submit">Simpan anggota</button>
        <a class="ml-3 text-sm text-emerald-800 underline" href="{{ route('residents.index') }}">Kembali ke data warga</a>
    </form>
@endsection
