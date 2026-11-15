@props(['area' => null, 'ready' => false, 'formId' => 'create-rw'])
@foreach (['village_name' => 'Kelurahan / Desa', 'district_name' => 'Kecamatan', 'city_name' => 'Kota / Kabupaten'] as $field => $label)
    @php($saved = $ready ? $area?->getAttribute($field) : null)
    <label class="block text-sm font-medium">{{ $label }}
        <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="{{ $field }}" value="{{ old('_form') === $formId ? old($field, $saved) : $saved }}" maxlength="120" @required($ready) @disabled(!$ready)>
    </label>
@endforeach
@unless ($ready)
    <p class="text-xs text-slate-600">Kolom identitas administrasi belum aktif. Terapkan file UPDATE-DATABASE.sql terbaru untuk mengisinya.</p>
@endunless
