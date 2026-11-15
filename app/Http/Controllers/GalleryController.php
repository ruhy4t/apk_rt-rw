<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\GalleryItem;
use App\Services\AreaAccess;
use App\Services\ContentPhotos;
use App\Services\ContentVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GalleryController extends Controller
{
    public function index(Request $request, ContentVisibility $visibility, AreaAccess $access)
    {
        $ready = Schema::hasTable('gallery_items');
        $items = $ready ? $visibility->visibleGallery(GalleryItem::query(), $request->user())->with(['photo', 'rtArea'])->latest()->paginate(12) : null;
        $canCreate = $request->user()?->is_active && ! $request->user()?->must_change_password && $request->user()?->hasRole('super_admin', 'rw_operator', 'rt_operator');
        $areas = $canCreate ? $access->manageableRtAreas($request->user())->orderBy('code')->get(['id', 'code', 'name']) : collect();

        return view('gallery', compact('items', 'ready', 'canCreate', 'areas', 'visibility'));
    }

    public function store(Request $request, AreaAccess $access, ContentPhotos $photos)
    {
        $this->ready();
        $data = $this->validate($request);
        $rtId = isset($data['rt_area_id']) ? (int) $data['rt_area_id'] : null;
        abort_unless($rtId === null ? $request->user()->hasRole('super_admin') : $access->canManageRt($request->user(), $rtId), 403);
        $item = $photos->save(new GalleryItem, [
            ...Arr::except($data, ['photo', 'remove_photo']),
            'rt_area_id' => $rtId, 'created_by' => $request->user()->id,
            'video_url' => $data['type'] === 'video' ? $data['video_url'] : null,
            'is_published' => $request->boolean('is_published'),
        ], $request);
        AuditEvent::record($request->user(), 'gallery.created', $item);

        return redirect()->route('gallery.index')->with('status', 'Item galeri berhasil disimpan.');
    }

    public function update(Request $request, int $galleryItem, ContentPhotos $photos, ContentVisibility $visibility)
    {
        $this->ready();
        $item = GalleryItem::query()->findOrFail($galleryItem);
        abort_unless($visibility->canManage($request->user(), $item), 403);
        $data = $this->validate($request, $item);
        $item = $photos->save($item, [
            ...Arr::except($data, ['photo', 'remove_photo', 'rt_area_id']),
            'video_url' => $item->type === 'video' ? $data['video_url'] : null,
            'is_published' => $request->boolean('is_published'),
        ], $request);
        AuditEvent::record($request->user(), 'gallery.updated', $item);

        return redirect()->route('gallery.index')->with('status', 'Item galeri berhasil diperbarui.');
    }

    private function ready(): void
    {
        abort_unless(Schema::hasTable('gallery_items'), 503, 'Galeri belum aktif. Administrator perlu mengimpor SQL galeri terlebih dahulu.');
    }

    private function validate(Request $request, ?GalleryItem $item = null): array
    {
        $data = $request->validate([
            'rt_area_id' => ['nullable', 'integer', 'exists:rt_areas,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'type' => $item ? ['required', 'in:'.$item->type] : ['required', 'in:photo,video'],
            'video_url' => ['nullable', 'required_if:type,video', 'string', 'max:2048', 'url:https'],
            'audience' => ['required', 'in:public,residents'],
            'is_published' => ['nullable', 'boolean'],
            ...ContentPhotos::rules(),
        ], [...ContentPhotos::messages(), 'type.in' => 'Jenis item tidak dapat diubah. Tambahkan item baru untuk jenis lain.', 'video_url.url' => 'Gunakan tautan video HTTPS yang lengkap.', 'video_url.required_if' => 'Tautan video wajib diisi.']);
        if ($data['type'] === 'photo' && ! $request->hasFile('photo') && (! $item || ! $item->photo()->exists() || $request->boolean('remove_photo'))) {
            throw ValidationException::withMessages(['photo' => 'Pilih foto untuk item galeri ini.']);
        }
        if ($data['type'] === 'video') {
            if ($request->hasFile('photo')) {
                throw ValidationException::withMessages(['photo' => 'Item video memakai tautan. Pilih jenis Foto untuk mengunggah gambar.']);
            }
            $parts = parse_url($data['video_url']);
            $host = strtolower($parts['host'] ?? '');
            if (isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443) || ! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/D', $host) || preg_match('/\.(local|localhost|internal|test|invalid)$/D', $host)) {
                throw ValidationException::withMessages(['video_url' => 'Gunakan tautan HTTPS dari situs video, tanpa nama pengguna atau kata sandi di alamatnya.']);
            }
        }

        return $data;
    }
}
