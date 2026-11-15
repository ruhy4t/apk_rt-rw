@extends('layouts.app')

@section('title', 'Pengumuman')

@section('content')
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section>
            <h1 class="text-2xl font-bold">Pengumuman</h1>
            <p class="mt-2 text-sm text-slate-600">Pastikan pengumuman tidak memuat data pribadi yang tidak seharusnya dipublikasikan.</p>
            <div class="mt-6 space-y-4">
                @forelse ($announcements as $announcement)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5">
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
                        <form class="mt-4 grid gap-3 border-t border-slate-100 pt-4" method="POST" action="{{ route('announcements.update', $announcement) }}">
                            @csrf
                            @method('PATCH')
                            <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="title" value="{{ $announcement->title }}" maxlength="180" required>
                            <textarea class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="body" rows="3" maxlength="10000" required>{{ $announcement->body }}</textarea>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <select class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="audience">
                                    <option value="public" @selected($announcement->audience === 'public')>Publik</option>
                                    <option value="residents" @selected($announcement->audience === 'residents')>Khusus warga</option>
                                </select>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="hidden" name="is_published" value="0">
                                    <input type="checkbox" name="is_published" value="1" @checked($announcement->is_published)>
                                    Terbitkan
                                </label>
                                <button class="rounded-lg border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-800" type="submit">Perbarui</button>
                            </div>
                        </form>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-500">Belum ada pengumuman.</p>
                @endforelse
            </div>
            <div class="mt-5">{{ $announcements->links() }}</div>
        </section>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="font-bold">Buat pengumuman</h2>
            <form class="mt-4 space-y-4" method="POST" action="{{ route('announcements.store') }}">
                @csrf
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
                <button class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white" type="submit">Simpan pengumuman</button>
            </form>
        </aside>
    </div>
@endsection
