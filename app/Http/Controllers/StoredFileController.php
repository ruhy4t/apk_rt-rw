<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\ServiceRequest;
use App\Models\StoredFile;
use App\Services\AreaAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoredFileController extends Controller
{
    public function store(Request $request, ServiceRequest $serviceRequest, AreaAccess $access): RedirectResponse
    {
        $this->authorizeRequest($request, $serviceRequest, $access);
        abort_unless(config('filesystems.private_files_enabled'), 503, 'Penyimpanan berkas privat belum diaktifkan.');

        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120',
            ],
        ]);

        $file = $data['file'];
        $key = 'service-requests/'.$serviceRequest->id.'/'.Str::uuid().'.'.$file->extension();
        Storage::disk('b2')->putFileAs(
            'service-requests/'.$serviceRequest->id,
            $file,
            basename($key),
            ['visibility' => 'private']
        );

        $originalName = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($file->getClientOriginalName()));

        $storedFile = $serviceRequest->files()->create([
            'uploaded_by' => $request->user()->id,
            'disk' => 'b2',
            'object_key' => $key,
            'original_name' => mb_substr($originalName ?: 'lampiran', 0, 255),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => $file->getSize(),
        ]);
        AuditEvent::record($request->user(), 'private_file.uploaded', $storedFile);

        return back()->with('status', 'Berkas berhasil diunggah.');
    }

    public function download(Request $request, StoredFile $storedFile, AreaAccess $access): StreamedResponse
    {
        abort_unless(config('filesystems.private_files_enabled'), 503, 'Penyimpanan berkas privat belum diaktifkan.');
        abort_unless($storedFile->fileable instanceof ServiceRequest, 404);
        $this->authorizeRequest($request, $storedFile->fileable, $access);

        $stream = Storage::disk($storedFile->disk)->readStream($storedFile->object_key);
        abort_unless(is_resource($stream), 404);

        return response()->streamDownload(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, $storedFile->original_name, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeRequest(Request $request, ServiceRequest $serviceRequest, AreaAccess $access): void
    {
        $user = $request->user();

        if ($user->hasRole('resident')) {
            abort_unless($serviceRequest->requester_id === $user->id, 404);

            return;
        }

        abort_unless(
            $user->hasRole('super_admin', 'rw_operator', 'rt_operator')
                && $serviceRequest->household
                && $access->canManageRt($user, $serviceRequest->household->rt_area_id),
            404
        );
    }
}
