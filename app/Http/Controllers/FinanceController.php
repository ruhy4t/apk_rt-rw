<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Contribution;
use App\Models\FinancialEntry;
use App\Models\Household;
use App\Services\AreaAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request, AreaAccess $access): View
    {
        $user = $request->user();
        $isResident = $user->hasRole('resident');

        if ($isResident) {
            $householdId = $user->resident()->value('household_id');
            $contributions = Contribution::query()
                ->where('household_id', $householdId ?? 0)
                ->latest('period')
                ->paginate(20);

            return view('management.finance', [
                'entries' => collect(),
                'contributions' => $contributions,
                'areas' => collect(),
                'households' => collect(),
                'isResident' => true,
            ]);
        }

        $areaIds = $access->manageableRtAreas($user)->pluck('id');
        $entries = FinancialEntry::query()
            ->whereIn('rt_area_id', $areaIds)
            ->with('rtArea:id,name,code')
            ->latest('occurred_on')
            ->paginate(20, ['*'], 'entries_page');
        $contributions = Contribution::query()
            ->whereHas('household', fn ($query) => $query->whereIn('rt_area_id', $areaIds))
            ->with('household:id,household_code,rt_area_id')
            ->latest('period')
            ->paginate(20, ['*'], 'contributions_page');
        $households = Household::query()
            ->whereIn('rt_area_id', $areaIds)
            ->orderBy('household_code')
            ->get(['id', 'household_code', 'rt_area_id']);
        $areas = $access->manageableRtAreas($user)->orderBy('name')->get(['id', 'name', 'code']);

        return view('management.finance', compact('entries', 'contributions', 'households', 'areas') + [
            'isResident' => false,
        ]);
    }

    public function storeEntry(Request $request, AreaAccess $access): RedirectResponse
    {
        $data = $request->validate([
            'rt_area_id' => ['required', 'integer', 'exists:rt_areas,id'],
            'direction' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'occurred_on' => ['required', 'date'],
            'public_summary' => ['nullable', 'boolean'],
        ]);

        abort_unless($access->canManageRt($request->user(), (int) $data['rt_area_id']), 403);

        $entry = FinancialEntry::query()->create([
            ...$data,
            'recorded_by' => $request->user()->id,
            'public_summary' => (bool) ($data['public_summary'] ?? false),
        ]);
        AuditEvent::record($request->user(), 'financial_entry.created', $entry);

        return back()->with('status', 'Transaksi berhasil dicatat.');
    }

    public function storeContribution(Request $request, AreaAccess $access): RedirectResponse
    {
        $data = $request->validate([
            'household_id' => ['required', 'integer', 'exists:households,id'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'period' => ['required', 'date_format:Y-m'],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $household = Household::query()->findOrFail($data['household_id']);
        abort_unless($access->canAccessHousehold($request->user(), $household) && ! $request->user()->hasRole('resident'), 403);

        $contribution = Contribution::query()->create([
            ...$data,
            'recorded_by' => $request->user()->id,
        ]);
        AuditEvent::record($request->user(), 'contribution.created', $contribution);

        return back()->with('status', 'Iuran berhasil dicatat.');
    }
}
