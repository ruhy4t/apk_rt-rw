<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\ServiceRequest;
use App\Services\AreaAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    public function index(Request $request, AreaAccess $access): View
    {
        $user = $request->user();
        $query = ServiceRequest::query()->with(['household:id,household_code,rt_area_id', 'files']);

        if ($user->hasRole('resident')) {
            $query->where('requester_id', $user->id);
        } elseif ($user->hasRole('super_admin', 'rw_operator', 'rt_operator')) {
            $areaIds = $access->manageableRtAreas($user)->pluck('id');
            $query->whereHas('household', fn ($household) => $household->whereIn('rt_area_id', $areaIds));
        } else {
            abort(403);
        }

        $requests = $query->latest()->paginate(20);

        return view('management.requests', [
            'requests' => $requests,
            'isResident' => $user->hasRole('resident'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('resident'), 403);

        $householdId = $user->resident()->value('household_id');
        abort_unless($householdId, 403);

        $data = $request->validate([
            'category' => ['required', 'in:letter,complaint,other'],
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $serviceRequest = ServiceRequest::query()->create([
            ...$data,
            'household_id' => $householdId,
            'requester_id' => $user->id,
            'status' => 'submitted',
        ]);
        AuditEvent::record($user, 'service_request.created', $serviceRequest);

        return back()->with('status', 'Permintaan layanan berhasil dikirim.');
    }

    public function update(Request $request, ServiceRequest $serviceRequest, AreaAccess $access): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super_admin', 'rw_operator', 'rt_operator'), 403);
        abort_unless(
            $serviceRequest->household
                && $access->canManageRt($request->user(), $serviceRequest->household->rt_area_id),
            403
        );

        $data = $request->validate([
            'status' => ['required', 'in:in_review,waiting_for_info,completed,rejected'],
            'response' => ['nullable', 'string', 'max:5000'],
        ]);

        if (in_array($serviceRequest->status, ['completed', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Permintaan yang sudah ditutup tidak dapat diubah.',
            ]);
        }

        $serviceRequest->update([
            ...$data,
            'assigned_to' => $request->user()->id,
        ]);
        AuditEvent::record($request->user(), 'service_request.updated', $serviceRequest);

        return back()->with('status', 'Status permintaan berhasil diperbarui.');
    }
}
