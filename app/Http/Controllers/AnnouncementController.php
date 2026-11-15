<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AuditEvent;
use App\Services\AreaAccess;
use App\Services\ContentPhotos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request, AreaAccess $access): View
    {
        $areaIds = $access->manageableRtAreas($request->user())->pluck('id');
        $announcements = Announcement::query()
            ->where(function ($query) use ($areaIds) {
                $query->whereNull('rt_area_id')->orWhereIn('rt_area_id', $areaIds);
            })
            ->with(['rtArea:id,name,code', 'photo'])
            ->latest()
            ->paginate(20);

        return view('management.announcements', compact('announcements'));
    }

    public function store(Request $request, AreaAccess $access, ContentPhotos $photos): RedirectResponse
    {
        $data = $request->validate([
            'rt_area_id' => ['nullable', 'integer', 'exists:rt_areas,id'],
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:10000'],
            'audience' => ['required', 'in:public,residents'],
            'is_published' => ['nullable', 'boolean'],
            ...ContentPhotos::rules(),
        ], ContentPhotos::messages());

        $rtAreaId = isset($data['rt_area_id']) ? (int) $data['rt_area_id'] : null;
        abort_unless(
            ($rtAreaId === null && $request->user()->hasRole('super_admin'))
                || ($rtAreaId !== null && $access->canManageRt($request->user(), $rtAreaId)),
            403
        );

        $published = (bool) ($data['is_published'] ?? false);
        $announcement = $photos->save(new Announcement, [
            ...Arr::except($data, ['photo', 'remove_photo']),
            'rt_area_id' => $rtAreaId,
            'created_by' => $request->user()->id,
            'is_published' => $published,
            'published_at' => $published ? now() : null,
        ], $request);
        AuditEvent::record($request->user(), 'announcement.created', $announcement);

        return back()->with('status', 'Pengumuman berhasil disimpan.');
    }

    public function update(Request $request, Announcement $announcement, AreaAccess $access, ContentPhotos $photos): RedirectResponse
    {
        abort_unless(
            $announcement->rt_area_id === null
                ? $request->user()->hasRole('super_admin')
                : $access->canManageRt($request->user(), $announcement->rt_area_id),
            403
        );

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:10000'],
            'audience' => ['required', 'in:public,residents'],
            'is_published' => ['required', 'boolean'],
            ...ContentPhotos::rules(),
        ], ContentPhotos::messages());

        $data['published_at'] = $data['is_published']
            ? ($announcement->published_at ?? now())
            : null;

        $announcement = $photos->save($announcement, Arr::except($data, ['photo', 'remove_photo']), $request);
        AuditEvent::record($request->user(), 'announcement.updated', $announcement);

        return back()->with('status', 'Pengumuman berhasil diperbarui.');
    }
}
