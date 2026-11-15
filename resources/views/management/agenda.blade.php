@extends('layouts.app')

@section('title', 'Agenda')

@section('content')
    <div>
        <section>
            <div class="page-heading"><div><h1>Agenda lingkungan</h1><p>Jadwalkan kegiatan dan atur informasi agenda lingkungan.</p></div><button class="button button-primary" type="button" data-open-dialog="create-agenda"><x-icon name="plus" /><span data-button-label>Tambah agenda</span></button></div>
            <div class="mt-6 space-y-4">
                @forelse ($events as $event)
                    <article class="rounded-lg border border-slate-200 bg-white p-5">
                        <div class="flex flex-wrap justify-between gap-2">
                            <h2 class="font-semibold">{{ $event->title }}</h2>
                            <span class="text-sm text-slate-500">{{ $event->starts_at->format('d-m-Y H:i') }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $event->audience === 'public' ? 'Publik' : 'Khusus warga' }} · {{ $event->is_published ? 'Terbit' : 'Draf' }}</p>
                        @if ($event->description)
                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $event->description }}</p>
                        @endif
                        <x-content-photo :content="$event" />
                        <details class="edit-section" @if ($errors->any() && old('_form') === 'edit-agenda-'.$event->id) open @endif>
                            <summary><x-icon name="edit" />Ubah agenda</summary>
                        <form class="mt-4 grid gap-3 border-t border-slate-100 pt-4" method="POST" enctype="multipart/form-data" action="{{ route('agenda.update', $event) }}">
                            <input type="hidden" name="_form" value="edit-agenda-{{ $event->id }}">
                            @csrf
                            @method('PATCH')
                            <x-photo-input :id="'edit-agenda-photo-'.$event->id" :existing="(bool) $event->photo" />
                            <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="title" aria-label="Judul agenda" value="{{ $event->title }}" maxlength="180" required>
                            <textarea class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="description" aria-label="Keterangan agenda" rows="2" maxlength="10000">{{ $event->description }}</textarea>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" type="datetime-local" name="starts_at" aria-label="Waktu mulai" value="{{ $event->starts_at->format('Y-m-d\TH:i') }}" required>
                                <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" type="datetime-local" name="ends_at" aria-label="Waktu selesai" value="{{ $event->ends_at?->format('Y-m-d\TH:i') }}">
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <select class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="audience" aria-label="Audiens">
                                    <option value="public" @selected($event->audience === 'public')>Publik</option>
                                    <option value="residents" @selected($event->audience === 'residents')>Khusus warga</option>
                                </select>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="hidden" name="is_published" value="0">
                                    <input type="checkbox" name="is_published" value="1" @checked($event->is_published)>
                                    Terbit
                                </label>
                                <button class="rounded-lg border border-brand-700 px-4 py-2 text-sm font-semibold text-brand-800" type="submit"><x-icon name="edit" /><span data-button-label>Perbarui</span></button>
                            </div>
                        </form>
                        </details>
                    </article>
                @empty
                    <p class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">Belum ada agenda.</p>
                @endforelse
            </div>
            <div class="mt-5">{{ $events->links() }}</div>
        </section>

        <dialog id="create-agenda" aria-labelledby="create-agenda-title" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-lg border border-slate-200 bg-white p-5 sm:p-7" @if ($errors->any() && old('_form') === 'create-agenda') data-auto-open @endif>
            <div class="flex items-start justify-between gap-4">
                <h2 id="create-agenda-title" class="text-xl font-bold"><x-icon name="plus" />Tambah agenda</h2>
                <button class="button" type="button" data-close-dialog><x-icon name="close" /><span data-button-label>Tutup</span></button>
            </div>
            <form class="mt-4 space-y-4" method="POST" enctype="multipart/form-data" action="{{ route('agenda.store') }}">
                @csrf
                <input type="hidden" name="_form" value="create-agenda">
                <x-photo-input id="new-agenda-photo" />
                @if (auth()->user()->hasRole('super_admin'))
                    <label class="block text-sm font-medium" for="rt_area_id">Cakupan
                        <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" id="rt_area_id" name="rt_area_id">
                            <option value="">Semua wilayah</option>
                            @foreach (\App\Models\RtArea::query()->orderBy('name')->get() as $area)
                                <option value="{{ $area->id }}">{{ $area->name }} · {{ $area->code }}</option>
                            @endforeach
                        </select>
                    </label>
                @else
                    @if (auth()->user()->hasRole('rw_operator'))
                        <label class="block text-sm font-medium" for="rt_area_id">Wilayah RT
                            <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" id="rt_area_id" name="rt_area_id" required>
                                @foreach (app(\App\Services\AreaAccess::class)->manageableRtAreas(auth()->user())->orderBy('name')->get() as $area)
                                    <option value="{{ $area->id }}">{{ $area->name }} · {{ $area->code }}</option>
                                @endforeach
                            </select>
                        </label>
                    @else
                        <input type="hidden" name="rt_area_id" value="{{ auth()->user()->rt_area_id }}">
                    @endif
                @endif
                <label class="block text-sm font-medium" for="title">Judul
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" id="title" name="title" maxlength="180" required>
                </label>
                <label class="block text-sm font-medium" for="description">Keterangan
                    <textarea class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" id="description" name="description" rows="3" maxlength="10000"></textarea>
                </label>
                <label class="block text-sm font-medium" for="starts_at">Mulai
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" id="starts_at" type="datetime-local" name="starts_at" required>
                </label>
                <label class="block text-sm font-medium" for="ends_at">Selesai (opsional)
                    <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" id="ends_at" type="datetime-local" name="ends_at">
                </label>
                <label class="block text-sm font-medium" for="audience">Audiens
                    <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" id="audience" name="audience">
                        <option value="public">Publik</option>
                        <option value="residents">Khusus warga</option>
                    </select>
                </label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_published" value="1"> Terbitkan</label>
                <button class="w-full rounded-lg bg-brand-700 px-4 py-2.5 font-semibold text-white" type="submit"><x-icon name="save" /><span data-button-label>Simpan agenda</span></button>
            </form>
        </dialog>
    </div>
@endsection
