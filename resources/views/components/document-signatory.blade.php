@props(['content'])
@php($signatory = $content->signatory_data)
@if (is_array($signatory) && isset($signatory['name'], $signatory['title']))
    <div class="mt-4 border-t border-slate-200 pt-3 text-sm">
        <p class="text-xs text-slate-500">Penandatangan</p>
        <p class="mt-1 font-semibold">{{ $signatory['name'] }}</p>
        <p>{{ $signatory['title'] }}</p>
        <p class="mt-1 text-xs text-slate-500">Untuk tanda tangan manual.</p>
    </div>
@endif
