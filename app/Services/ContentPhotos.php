<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ContentPhotos
{
    public static function rules(): array
    {
        return [
            'photo' => ['nullable', 'file', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:1024', 'dimensions:max_width=2400,max_height=2400'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    public static function messages(): array
    {
        return [
            'photo.file' => 'Foto harus berupa berkas gambar.',
            'photo.image' => 'Foto tidak dapat dibaca. Gunakan berkas JPG, PNG, atau WebP yang valid.',
            'photo.mimetypes' => 'Gunakan foto JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 1 MB.',
            'photo.dimensions' => 'Ukuran foto maksimal 2400 × 2400 piksel. Perkecil foto lalu unggah kembali.',
            'photo.uploaded' => 'Foto belum berhasil diunggah. Periksa ukuran berkas dan coba lagi.',
        ];
    }

    public function save(Model $content, array $data, Request $request): Model
    {
        $file = $request->file('photo');
        if ($file && $request->boolean('remove_photo')) {
            throw ValidationException::withMessages(['photo' => 'Pilih foto pengganti atau hapus foto lama, jangan keduanya.']);
        }
        if ($file && ! config('filesystems.private_files_enabled')) {
            throw ValidationException::withMessages(['photo' => 'Penyimpanan foto belum aktif. Simpan tanpa foto atau hubungi administrator.']);
        }
        $key = null;
        if ($file) {
            $extension = match ($file->getMimeType()) {
                'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
                default => throw ValidationException::withMessages(['photo' => 'Gunakan foto JPG, PNG, atau WebP.']),
            };
            $key = 'content-photos/'.Str::uuid().'.'.$extension;
            try {
                $written = Storage::disk('b2')->putFileAs('content-photos', $file, basename($key), ['visibility' => 'private']);
                if (! $written) {
                    throw new \RuntimeException('Photo write failed');
                }
            } catch (Throwable) {
                throw ValidationException::withMessages(['photo' => 'Foto belum berhasil diunggah. Coba lagi; perubahan belum disimpan.']);
            }
        }
        try {
            return DB::transaction(function () use ($content, $data, $request, $file, $key) {
                if ($content->exists) {
                    $content = $content->newQuery()->whereKey($content->getKey())->lockForUpdate()->firstOrFail();
                }
                $content->fill($data)->save();
                if ($file || $request->boolean('remove_photo')) {
                    $old = $content->photo()->first();
                    if ($old) {
                        $old->delete();
                        DB::afterCommit(fn () => $this->discard($old->disk, $old->object_key));
                    }
                    if ($file) {
                        $content->photo()->create([
                            'uploaded_by' => $request->user()->id,
                            'disk' => 'b2', 'object_key' => $key, 'original_name' => 'foto.'.pathinfo($key, PATHINFO_EXTENSION),
                            'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(),
                        ]);
                    }
                }

                return $content;
            });
        } catch (Throwable $exception) {
            if ($key) {
                $this->discard('b2', $key);
            }
            throw $exception;
        }
    }

    private function discard(string $disk, string $key): void
    {
        try {
            Storage::disk($disk)->delete($key);
        } catch (Throwable) {
            // An unavailable object store may leave an inaccessible orphan; never expose its key.
        }
    }
}
