<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\CommunityEvent;
use App\Services\AreaAccess;
use App\Services\ContentPhotos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function index(Request $request, AreaAccess $access): View
    {
        $areaIds = $access->manageableRtAreas($request->user())->pluck('id');
        $events = CommunityEvent::query()
            ->with('photo')
            ->where(function ($query) use ($areaIds) {
                $query->whereNull('rt_area_id')->orWhereIn('rt_area_id', $areaIds);
            })
            ->latest('starts_at')
            ->paginate(20);

        return view('management.agenda', compact('events'));
    }

    public function store(Request $request, AreaAccess $access, ContentPhotos $photos): RedirectResponse
    {
        $data = $request->validate([
            'rt_area_id' => ['nullable', 'integer', 'exists:rt_areas,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
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

        $event = $photos->save(new CommunityEvent, [
            ...Arr::except($data, ['photo', 'remove_photo']),
            'rt_area_id' => $rtAreaId,
            'created_by' => $request->user()->id,
            'is_published' => (bool) ($data['is_published'] ?? false),
        ], $request);
        AuditEvent::record($request->user(), 'event.created', $event);

        return back()->with('status', 'Agenda berhasil disimpan.');
    }

    public function update(Request $request, CommunityEvent $event, AreaAccess $access, ContentPhotos $photos): RedirectResponse
    {
        abort_unless(
            $event->rt_area_id === null
                ? $request->user()->hasRole('super_admin')
                : $access->canManageRt($request->user(), $event->rt_area_id),
            403
        );

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'audience' => ['required', 'in:public,residents'],
            'is_published' => ['required', 'boolean'],
            ...ContentPhotos::rules(),
        ], ContentPhotos::messages());

        $event = $photos->save($event, Arr::except($data, ['photo', 'remove_photo']), $request);
        AuditEvent::record($request->user(), 'event.updated', $event);

        return back()->with('status', 'Agenda berhasil diperbarui.');
    }
}
