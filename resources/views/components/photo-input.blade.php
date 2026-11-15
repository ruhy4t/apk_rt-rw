@props(['id', 'existing' => false])
<div>
    <label class="mb-1 block text-sm font-medium" for="{{ $id }}"><x-icon name="image" />{{ $existing ? 'Ganti foto (opsional)' : 'Foto (opsional)' }}</label>
    <input class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" id="{{ $id }}" type="file" name="photo" accept="image/jpeg,image/png,image/webp" @disabled(!config('filesystems.private_files_enabled')) aria-describedby="{{ $id }}-hint">
    <p id="{{ $id }}-hint" class="mt-1 text-xs text-slate-500">JPG, PNG, atau WebP; maksimal 1 MB dan 2400 × 2400 piksel. Foto mengikuti audiens dan status terbit konten. Pastikan foto boleh dibagikan dan tidak memuat dokumen pribadi atau lokasi sensitif.</p>
    @unless (config('filesystems.private_files_enabled'))<p class="mt-1 text-xs text-slate-600">Penyimpanan foto belum diaktifkan oleh administrator.</p>@endunless
    @if ($existing)<label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_photo" value="1">Hapus foto saat menyimpan</label>@endif
</div>
