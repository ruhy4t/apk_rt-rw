@extends('layouts.app')
@section('title', 'Galeri foto dan video')
@section('content')
    <div class="page-heading"><div><h1>Galeri foto dan video</h1><p>Dokumentasi kegiatan lingkungan. Tonton video langsung di galeri.</p></div>
        @if ($canCreate && $ready)<button class="button button-primary" type="button" data-open-dialog="create-gallery"><x-icon name="plus" /><span data-button-label>Tambah foto atau video</span></button>@endif
    </div>
    @unless ($ready)
        <p class="empty-state mt-6">Galeri belum tersedia.@if ($canCreate) Administrator perlu mengimpor SQL galeri terlebih dahulu.@endif</p>
    @else
        <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($items as $item)
                <article class="rounded-lg border border-slate-200 bg-white p-5">
                    <p class="text-xs text-slate-500">{{ $item->type === 'photo' ? 'Foto' : 'Video' }} · {{ $item->rtArea?->name ?? 'Seluruh RW' }} · {{ $item->audience === 'public' ? 'Publik' : 'Khusus warga' }}@if (!$item->is_published) · Draf @endif</p>
                    <h2 class="mt-2 text-lg font-semibold">{{ $item->title }}</h2>
                    @if ($item->type === 'photo')<x-content-photo :content="$item" />@endif
                    @if ($item->description)<p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $item->description }}</p>@endif
                    @if ($item->type === 'video')
                        <x-video-embed :url="$item->video_url" :title="$item->title" />
                        <a class="button mt-4" href="{{ $item->video_url }}" target="_blank" rel="noopener noreferrer nofollow"><x-icon name="video" />Buka video di {{ parse_url($item->video_url, PHP_URL_HOST) }}</a>
                    @endif
                    @if ($canCreate && ($item->rt_area_id === null ? auth()->user()->hasRole('super_admin') : $areas->contains('id', $item->rt_area_id)))
                        <details class="edit-section" @if($errors->any() && old('_form') === 'edit-gallery-'.$item->id) open @endif>
                            <summary><x-icon name="edit" />Ubah item</summary>
                            <form class="mt-4 space-y-3" method="POST" enctype="multipart/form-data" action="{{ route('gallery.update', $item) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="_form" value="edit-gallery-{{ $item->id }}"><input type="hidden" name="type" value="{{ $item->type }}">
                                <label class="block text-sm">Judul<input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" name="title" value="{{ $item->title }}" maxlength="180" required></label>
                                <label class="block text-sm">Keterangan<textarea class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" name="description" rows="3" maxlength="3000">{{ $item->description }}</textarea></label>
                                @if ($item->type === 'photo')<x-photo-input :id="'gallery-photo-'.$item->id" />
                                @else<label class="block text-sm">Tautan video<input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" type="url" name="video_url" value="{{ $item->video_url }}" maxlength="2048" required></label>@endif
                                <label class="block text-sm">Audiens<select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" name="audience"><option value="residents" @selected($item->audience === 'residents')>Khusus warga</option><option value="public" @selected($item->audience === 'public')>Publik</option></select></label>
                                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_published" value="0"><input type="checkbox" name="is_published" value="1" @checked($item->is_published)>Terbitkan</label>
                                <button class="button button-primary" type="submit"><x-icon name="save" /><span data-button-label>Simpan perubahan</span></button>
                            </form>
                        </details>
                    @endif
                </article>
            @empty<p class="empty-state sm:col-span-2 xl:col-span-3">Belum ada foto atau video yang dapat ditampilkan.</p>@endforelse
        </div>
        <div class="mt-5">{{ $items->links() }}</div>
    @endunless
    @if ($canCreate && $ready)
        <dialog id="create-gallery" aria-labelledby="create-gallery-title" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-lg border border-slate-200 bg-white p-5 sm:p-7" @if($errors->any() && old('_form') === 'create-gallery') data-auto-open @endif>
            <div class="flex items-start justify-between gap-4"><h2 id="create-gallery-title" class="text-xl font-bold"><x-icon name="image" />Tambah foto atau video</h2><button class="button" type="button" data-close-dialog><x-icon name="close" /><span data-button-label>Tutup</span></button></div>
            <p class="mt-4 text-sm text-slate-600">Konten publik dapat dibaca tanpa login. Jangan unggah dokumen identitas atau foto yang belum mendapat izin untuk dibagikan.</p>
            <form class="mt-4 space-y-4" method="POST" enctype="multipart/form-data" action="{{ route('gallery.store') }}">
                @csrf<input type="hidden" name="_form" value="create-gallery">
                <label class="block text-sm font-medium">Cakupan<select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="rt_area_id" @required(!auth()->user()->hasRole('super_admin'))>
                    @if(auth()->user()->hasRole('super_admin'))<option value="">Seluruh RW</option>@endif
                    @foreach($areas as $area)<option value="{{ $area->id }}">{{ $area->name }} · {{ $area->code }}</option>@endforeach
                </select></label>
                <label class="block text-sm font-medium">Judul<input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="title" maxlength="180" value="{{ old('_form') === 'create-gallery' ? old('title') : '' }}" required></label>
                <label class="block text-sm font-medium">Keterangan (opsional)<textarea class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="description" rows="3" maxlength="3000">{{ old('_form') === 'create-gallery' ? old('description') : '' }}</textarea></label>
                <label class="block text-sm font-medium">Jenis<select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="type" data-gallery-type><option value="photo">Foto</option><option value="video" @selected(old('_form') === 'create-gallery' && old('type') === 'video')>Tautan video</option></select></label>
                <div data-gallery-photo><x-photo-input id="new-gallery-photo" /></div>
                <div data-gallery-video><label class="block text-sm font-medium">Tautan video HTTPS<input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" type="url" name="video_url" maxlength="2048" placeholder="https://www.youtube.com/watch?v=..." value="{{ old('_form') === 'create-gallery' ? old('video_url') : '' }}"></label><p class="mt-1 text-xs text-slate-500">YouTube, Instagram, TikTok, atau Vimeo ditampilkan sebagai embed. Gunakan tautan video lengkap, bukan kode HTML. Video harus mengizinkan penyematan dan tidak diputar otomatis.</p></div>
                <label class="block text-sm font-medium">Audiens<select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="audience"><option value="residents">Khusus warga</option><option value="public">Publik</option></select></label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_published" value="1">Terbitkan sekarang</label>
                <button class="button button-primary w-full" type="submit"><x-icon name="save" /><span data-button-label>Simpan item galeri</span></button>
            </form>
        </dialog>
        <script>
            document.querySelectorAll('[data-gallery-type]').forEach(select => {
                const update = () => {
                    const form = select.form;
                    const photo = select.value === 'photo';
                    form.querySelector('[data-gallery-photo]').hidden = !photo;
                    form.querySelector('[data-gallery-video]').hidden = photo;
                    form.querySelector('[name="photo"]').disabled = !photo || {{ config('filesystems.private_files_enabled') ? 'false' : 'true' }};
                    form.querySelector('[name="video_url"]').disabled = photo;
                };
                select.addEventListener('change', update); update();
            });
        </script>
    @endif
@endsection
