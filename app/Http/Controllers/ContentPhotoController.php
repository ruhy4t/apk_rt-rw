<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\CommunityEvent;
use App\Models\GalleryItem;
use App\Models\StoredFile;
use App\Services\ContentVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ContentPhotoController extends Controller
{
    public function show(Request $request, StoredFile $storedFile, ContentVisibility $visibility)
    {
        $content = $storedFile->fileable;
        abort_unless($content instanceof Announcement || $content instanceof CommunityEvent || $content instanceof GalleryItem, 404);
        abort_unless($visibility->canRead($request->user(), $content), 404);
        abort_unless(in_array($storedFile->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true), 404);
        abort_unless(config('filesystems.private_files_enabled'), 503, 'Penyimpanan foto belum aktif.');
        try {
            $stream = Storage::disk($storedFile->disk)->readStream($storedFile->object_key);
        } catch (Throwable) {
            abort(503, 'Foto belum dapat dimuat. Silakan coba lagi.');
        }
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, ['Content-Type' => $storedFile->mime_type, 'Content-Disposition' => 'inline', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
