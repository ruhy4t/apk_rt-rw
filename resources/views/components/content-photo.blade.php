@props(['content'])
@if ($content->photo && config('filesystems.private_files_enabled'))
    <figure class="mt-4">
        <a href="{{ route('content-photos.show', $content->photo) }}" target="_blank" rel="noopener" aria-label="Buka foto: {{ $content->title }}">
            <img class="max-h-80 w-full rounded-lg object-contain bg-slate-50" src="{{ route('content-photos.show', $content->photo) }}" alt="Foto untuk {{ $content->title }}" loading="lazy" decoding="async" width="800" height="450">
        </a>
    </figure>
@endif
