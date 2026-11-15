@props(['url', 'title'])
@php($embed = \App\Services\VideoEmbed::fromUrl($url))
@if ($embed)
    <div class="video-embed mt-4" data-aspect="{{ $embed['aspect'] }}">
        <iframe src="{{ $embed['src'] }}" title="{{ $title }} — {{ $embed['provider'] }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" sandbox="allow-scripts allow-same-origin allow-presentation allow-popups" allow="encrypted-media; fullscreen; picture-in-picture" allowfullscreen></iframe>
    </div>
    <p class="mt-2 text-xs text-slate-500">Pemutar {{ $embed['provider'] }} dimuat dari situs asal. Jika video tidak tersedia atau penyematannya dibatasi, gunakan tautan di bawah.</p>
@else
    <p class="mt-3 text-sm text-slate-600">Video ini belum dapat diputar di galeri. Gunakan tautan di bawah untuk membukanya di situs asal.</p>
@endif
