@extends('layouts.app')

@section('title', 'Tambah anggota keluarga')

@section('content')
    <h1>Tambah anggota keluarga</h1>
    <p class="mt-2 text-sm text-slate-600">Rumah tangga: {{ $household->household_code }}</p>
    <p class="mt-2 text-sm text-slate-600">{{ $hasHead ? 'Kepala keluarga sudah ditetapkan. Untuk menggantinya, buka Ganti kepala keluarga di Data warga.' : 'Rumah tangga ini belum memiliki kepala keluarga. Pilih Kepala Keluarga pada hubungan keluarga bila warga ini akan menjadi kepala keluarga.' }}</p>
    <form class="mt-6 max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-5" method="POST" action="{{ route('residents.people.store', $household) }}">
        @csrf
        <label class="block text-sm font-medium">Hubungan keluarga
            <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" name="relationship" required>
                <option value="">Pilih hubungan</option>
                @unless ($hasHead)<option value="Kepala Keluarga" @selected(old('relationship') === 'Kepala Keluarga')>Kepala Keluarga</option>@endunless
                @foreach (['Istri', 'Suami', 'Anak', 'Famili Lain'] as $relationship)
                    <option value="{{ $relationship }}" @selected(old('relationship') === $relationship)>{{ $relationship }}</option>
                @endforeach
            </select>
        </label>
        @include('management.resident-fields', ['editingResident' => null])
        <button class="rounded-lg bg-brand-700 px-4 py-2.5 font-semibold text-white" type="submit"><x-icon name="save" /><span data-button-label>Simpan anggota</span></button>
        <a class="ml-3 text-sm text-brand-800 underline" href="{{ route('residents.index') }}">Kembali ke data warga</a>
    </form>
@endsection
