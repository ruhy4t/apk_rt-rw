@extends('layouts.app')

@section('title', 'Layanan warga')

@section('content')
    <h1 class="text-2xl font-bold">Layanan warga</h1>
    <p class="mt-2 text-sm text-slate-600">Permintaan dan lampiran bersifat privat. Unggah dokumen hanya aktif setelah B2 disiapkan dan disetujui.</p>

    @if ($isResident)
        <form class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5" method="POST" action="{{ route('requests.store') }}">
            @csrf
            <h2 class="font-bold">Buat permintaan</h2>
            <label class="text-sm font-medium">Jenis layanan
                <select class="mt-1 w-full max-w-sm rounded-lg border border-slate-300 px-3 py-2 font-normal" name="category">
                    <option value="letter">Surat keterangan</option>
                    <option value="complaint">Aduan</option>
                    <option value="other">Layanan lain</option>
                </select>
            </label>
            <label class="text-sm font-medium">Judul
                <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="subject" maxlength="180" required>
            </label>
            <label class="text-sm font-medium">Keterangan
                <textarea class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-normal" name="description" rows="4" maxlength="5000" required></textarea>
            </label>
            <button class="w-fit rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white" type="submit">Kirim permintaan</button>
        </form>
    @endif

    <section class="mt-8 space-y-4">
        @forelse ($requests as $serviceRequest)
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800">{{ str_replace('_', ' ', $serviceRequest->category) }} · {{ str_replace('_', ' ', $serviceRequest->status) }}</p>
                        <h2 class="mt-1 font-semibold">{{ $serviceRequest->subject }}</h2>
                        @unless ($isResident)
                            <p class="mt-1 text-sm text-slate-500">Rumah tangga: {{ $serviceRequest->household->household_code }} · Pemohon: {{ $serviceRequest->requester->name }}</p>
                        @endunless
                    </div>
                    <time class="text-xs text-slate-500">{{ $serviceRequest->created_at->format('d-m-Y H:i') }}</time>
                </div>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $serviceRequest->description }}</p>
                @if ($serviceRequest->response)
                    <div class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-950">
                        <p class="font-semibold">Tanggapan pengurus</p>
                        <p class="mt-1 whitespace-pre-line">{{ $serviceRequest->response }}</p>
                    </div>
                @endif
                @if ($serviceRequest->files->isNotEmpty())
                    <div class="mt-4">
                        <p class="text-sm font-semibold">Lampiran</p>
                        <ul class="mt-2 space-y-1">
                            @foreach ($serviceRequest->files as $file)
                                <li><a class="text-sm font-medium text-emerald-800 underline" href="{{ route('files.download', $file) }}">{{ $file->original_name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($isResident)
                    @if (config('filesystems.private_files_enabled'))
                        <form class="mt-4 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4" method="POST" enctype="multipart/form-data" action="{{ route('requests.files.store', $serviceRequest) }}">
                            @csrf
                            <label class="text-sm font-medium">Lampiran PDF/JPG/PNG (maks. {{ config('filesystems.private_file_max_kb') / 1024 }} MB)
                                <input class="mt-1 block max-w-full text-sm" type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
                            </label>
                            <button class="rounded-lg border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-800" type="submit">Unggah berkas privat</button>
                        </form>
                    @else
                        <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">Unggah lampiran belum tersedia; konfigurasi B2 privat masih dinonaktifkan.</p>
                    @endif
                @else
                    @if (! in_array($serviceRequest->status, ['completed', 'rejected'], true))
                        <form class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-[12rem_minmax(0,1fr)_auto]" method="POST" action="{{ route('requests.update', $serviceRequest) }}">
                            @csrf
                            @method('PATCH')
                            <select class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="status">
                                @foreach (['in_review' => 'Ditinjau', 'waiting_for_info' => 'Menunggu informasi', 'completed' => 'Selesai', 'rejected' => 'Ditolak'] as $value => $label)
                                    <option value="{{ $value }}" @selected($serviceRequest->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input class="rounded-lg border border-slate-300 px-3 py-2 text-sm" name="response" value="{{ $serviceRequest->response }}" placeholder="Tanggapan untuk pemohon" maxlength="5000">
                            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white" type="submit">Perbarui</button>
                        </form>
                    @endif
                @endif
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-500">Belum ada permintaan layanan.</p>
        @endforelse
    </section>
    <div class="mt-5">{{ $requests->links() }}</div>
@endsection
