@extends('layouts.app')

@section('title', 'Pengumuman')

@section('content')
    <div>
        <section>
            <div class="page-heading"><div><h1>Pengumuman</h1><p>Kelola pengumuman publik dan khusus warga sesuai wilayah.</p></div><button class="button button-primary" type="button" data-open-dialog="create-announcement"><x-icon name="plus" /><span data-button-label>Tambah pengumuman</span></button></div>
            <div class="mt-6 space-y-4">
                @forelse ($announcements as $announcement)
                    <article class="rounded-lg border border-slate-200 bg-white p-5">
                        <div class="flex flex-wrap justify-between gap-3">
                            <div>
                                <h2 class="font-semibold">{{ $announcement->title }}</h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $announcement->audience === 'public' ? 'Publik' : 'Khusus warga' }}
                                    · {{ $announcement->rtArea?->name ?? 'Semua wilayah' }}
                                    · {{ $announcement->is_published ? 'Terbit' : 'Draf' }}
                                </p>
                            </div>
                            <time class="text-xs text-slate-500">{{ $announcement->created_at->format('d-m-Y') }}</time>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $announcement->body }}</p>
                        <x-content-photo :content="$announcement" />
                        <x-document-signatory :content="$announcement" />
                        @if ($announcement->is_published && ! $announcement->signatory_data)
                            <p class="mt-3 text-xs text-slate-600">Penandatangan pengumuman lama belum dicatat. Untuk menerbitkan ulang, simpan sebagai draf terlebih dahulu.</p>
                        @endif
                        <details class="edit-section" @if ($errors->any() && old('_form') === 'edit-announcements-'.$announcement->id) open @endif>
                            <summary><x-icon name="edit" />Ubah pengumuman</summary>
                        <form class="mt-4 grid gap-3 border-t border-slate-100 pt-4" method="POST" enctype="multipart/form-data" action="{{ route('announcements.update', $announcement) }}">
                            <input type="hidden" name="_form" value="edit-announcements-{{ $announcement->id }}">
                            @csrf
                            @method('PATCH')
                            <x-photo-input :id="'edit-announcement-photo-'.$announcement->id" :existing="(bool) $announcement->photo" />
                            <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="title" aria-label="Judul pengumuman" value="{{ $announcement->title }}" maxlength="180" required>
                            <textarea class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="body" aria-label="Isi pengumuman" rows="3" maxlength="10000" required>{{ $announcement->body }}</textarea>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <select class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="audience" aria-label="Audiens">
                                    <option value="public" @selected($announcement->audience === 'public')>Publik</option>
                                    <option value="residents" @selected($announcement->audience === 'residents')>Khusus warga</option>
                                </select>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="hidden" name="is_published" value="0">
                                    <input type="checkbox" name="is_published" value="1" @checked($announcement->is_published)>
                                    Terbitkan
                                </label>
                                <button class="rounded-lg border border-brand-700 px-4 py-2 text-sm font-semibold text-brand-800" type="submit"><x-icon name="edit" /><span data-button-label>Perbarui</span></button>
                            </div>
                        </form>
                        </details>
                    </article>
                @empty
                    <p class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">Belum ada pengumuman.</p>
                @endforelse
            </div>
            <div class="mt-5">{{ $announcements->links() }}</div>
        </section>

        <dialog id="create-announcement" aria-labelledby="create-announcement-title" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-lg border border-slate-200 bg-white p-5 sm:p-7" @if ($errors->any() && old('_form') === 'create-announcement') data-auto-open @endif>
            <div class="flex items-start justify-between gap-4">
                <h2 id="create-announcement-title" class="text-xl font-bold"><x-icon name="plus" />Tambah pengumuman</h2>
                <button class="button" type="button" data-close-dialog><x-icon name="close" /><span data-button-label>Tutup</span></button>
            </div>
            <p class="mt-4 text-sm text-slate-600">Pengumuman publik bisa dibaca tanpa login. Jangan cantumkan NIK, nomor KK, atau data pribadi warga di dalamnya.</p>
            <form class="mt-4 space-y-4" method="POST" enctype="multipart/form-data" action="{{ route('announcements.store') }}">
                @csrf
                <input type="hidden" name="_form" value="create-announcement">
                <x-photo-input id="new-announcement-photo" />
                @if (auth()->user()->hasRole('super_admin'))
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="rt_area_id">Cakupan</label>
                        <select class="w-full rounded-lg border border-slate-300 px-3 py-2" id="rt_area_id" name="rt_area_id">
                            <option value="">Semua wilayah</option>
                            @foreach (\App\Models\RtArea::query()->with('rwArea')->orderBy('name')->get() as $area)
                                <option value="{{ $area->id }}">{{ $area->name }} · {{ $area->rwArea->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    @if (auth()->user()->hasRole('rw_operator'))
                        <div>
                            <label class="mb-1 block text-sm font-medium" for="rt_area_id">Wilayah RT</label>
                            <select class="w-full rounded-lg border border-slate-300 px-3 py-2" id="rt_area_id" name="rt_area_id" required>
                                @foreach (app(\App\Services\AreaAccess::class)->manageableRtAreas(auth()->user())->orderBy('name')->get() as $area)
                                    <option value="{{ $area->id }}">{{ $area->name }} · {{ $area->code }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="rt_area_id" value="{{ auth()->user()->rt_area_id }}">
                    @endif
                @endif
                <div>
                    <label class="mb-1 block text-sm font-medium" for="title">Judul</label>
                    <input class="w-full rounded-lg border border-slate-300 px-3 py-2" id="title" name="title" maxlength="180" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="body">Isi pengumuman</label>
                    <textarea class="w-full rounded-lg border border-slate-300 px-3 py-2" id="body" name="body" rows="5" maxlength="10000" required></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="audience">Audiens</label>
                    <select class="w-full rounded-lg border border-slate-300 px-3 py-2" id="audience" name="audience">
                        <option value="public">Publik</option>
                        <option value="residents">Khusus warga</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_published" value="1">
                    Terbitkan sekarang
                </label>
                <button class="w-full rounded-lg bg-brand-700 px-4 py-2.5 font-semibold text-white" type="submit"><x-icon name="save" /><span data-button-label>Simpan pengumuman</span></button>
            </form>
        </dialog>
    </div>
@endsection
