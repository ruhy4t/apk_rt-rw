@props(['area', 'level', 'identityReady' => false])
@php($formId = 'edit-area-'.strtolower($level).'-'.$area->id)
<details class="edit-section" @if($errors->any() && old('_form') === $formId) open @endif>
    <summary><x-icon name="edit" />Ubah wilayah {{ $level }}</summary>
    <form class="mt-3 space-y-3" method="POST" action="{{ route($level === 'RT' ? 'admin.rt.update' : 'admin.rw.update', $area) }}">
        @csrf @method('PATCH')
        <input type="hidden" name="_form" value="{{ $formId }}">
        <label class="block text-sm font-medium">Kode {{ $level }}
            <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="code" value="{{ old('_form') === $formId ? old('code', $area->code) : $area->code }}" maxlength="32" required>
        </label>
        <label class="block text-sm font-medium">Nama wilayah {{ $level }}
            <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="name" value="{{ $area->name }}" maxlength="120" required>
        </label>
        @if ($level === 'RW')
            <x-rw-identity-fields :area="$area" :ready="$identityReady" :form-id="$formId" />
        @endif
        <p class="text-xs text-slate-500">Isi nama wilayah, misalnya {{ $level }} 05. Nama ketua diatur melalui formulir penetapan ketua. Data warga tetap terhubung ke wilayah ini.</p>
        <button class="button button-primary" type="submit"><x-icon name="save" /><span data-button-label>Simpan wilayah {{ $level }}</span></button>
    </form>
</details>
