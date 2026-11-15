<?php

namespace App\Http\Controllers;

use App\Models\AssetLoan;
use App\Models\AuditEvent;
use App\Models\CommunityAsset;
use App\Services\AreaAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request, AreaAccess $access): View
    {
        $user = $request->user();
        $areaIds = $access->manageableRtAreas($user)->pluck('id');

        if ($user->hasRole('resident')) {
            $household = $user->resident()->with('household')->first()?->household;
            $areaIds = collect([$household?->rt_area_id])->filter()->values();
        }

        $assets = CommunityAsset::query()
            ->whereIn('rt_area_id', $areaIds)
            ->orderBy('name')
            ->paginate(20, ['*'], 'assets_page');

        $loans = AssetLoan::query()
            ->whereHas('asset', fn ($query) => $query->whereIn('rt_area_id', $areaIds))
            ->when($user->hasRole('resident'), fn ($query) => $query->where('requested_by', $user->id))
            ->with(['asset:id,name,quantity', 'household:id,household_code'])
            ->latest()
            ->paginate(20, ['*'], 'loans_page');

        return view('management.assets', [
            'assets' => $assets,
            'loans' => $loans,
            'areas' => $user->hasRole('resident')
                ? collect()
                : $access->manageableRtAreas($user)->orderBy('name')->get(['id', 'name', 'code']),
            'household' => $user->resident()->with('household')->first()?->household,
            'isResident' => $user->hasRole('resident'),
        ]);
    }

    public function store(Request $request, AreaAccess $access): RedirectResponse
    {
        $data = $request->validate([
            'rt_area_id' => ['required', 'integer', 'exists:rt_areas,id'],
            'name' => ['required', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:80'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'condition' => ['nullable', 'string', 'max:32'],
            'public_summary' => ['nullable', 'boolean'],
        ]);

        abort_unless($access->canManageRt($request->user(), (int) $data['rt_area_id']), 403);

        $asset = CommunityAsset::query()->create([
            ...$data,
            'created_by' => $request->user()->id,
            'public_summary' => (bool) ($data['public_summary'] ?? false),
        ]);
        AuditEvent::record($request->user(), 'asset.created', $asset);

        return back()->with('status', 'Inventaris berhasil ditambahkan.');
    }

    public function requestLoan(Request $request, CommunityAsset $asset): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('resident'), 403);

        $household = $user->resident()->with('household')->first()?->household;
        abort_unless($household && $household->rt_area_id === $asset->rt_area_id, 403);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'starts_at' => ['nullable', 'date', 'after_or_equal:today'],
            'due_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        DB::transaction(function () use ($asset, $household, $user, $data) {
            $lockedAsset = CommunityAsset::query()->lockForUpdate()->findOrFail($asset->id);
            $reserved = AssetLoan::query()
                ->where('asset_id', $lockedAsset->id)
                ->whereIn('status', ['pending', 'approved'])
                ->sum('quantity');

            if ($reserved + (int) $data['quantity'] > $lockedAsset->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah yang diminta melebihi inventaris yang tersedia.',
                ]);
            }

            $loan = AssetLoan::query()->create([
                ...$data,
                'asset_id' => $lockedAsset->id,
                'household_id' => $household->id,
                'requested_by' => $user->id,
                'status' => 'pending',
            ]);
            AuditEvent::record($user, 'asset_loan.requested', $loan);
        });

        return back()->with('status', 'Permintaan peminjaman berhasil dikirim.');
    }

    public function updateLoan(Request $request, AssetLoan $loan, AreaAccess $access): RedirectResponse
    {
        abort_unless($access->canManageRt($request->user(), $loan->asset->rt_area_id), 403);

        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected,returned'],
        ]);

        if ($loan->status !== 'pending' && ! ($loan->status === 'approved' && $data['status'] === 'returned')) {
            throw ValidationException::withMessages([
                'status' => 'Status peminjaman ini tidak dapat diubah dengan aksi tersebut.',
            ]);
        }

        $loan->update([
            'status' => $data['status'],
            'approved_by' => $request->user()->id,
            'returned_at' => $data['status'] === 'returned' ? now() : null,
        ]);
        AuditEvent::record($request->user(), 'asset_loan.status_changed', $loan);

        return back()->with('status', 'Status peminjaman berhasil diperbarui.');
    }
}
