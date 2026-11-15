<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\CommunityEvent;
use App\Models\FinancialEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PublicHomeController extends Controller
{
    public function __invoke(): View
    {
        $announcements = Announcement::query()
            ->with('photo')
            ->where('audience', 'public')
            ->where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->latest('published_at')
            ->limit(5)
            ->get(['id', 'title', 'body', 'published_at', ...(Schema::hasColumn('announcements', 'signatory_data') ? ['signatory_data'] : [])]);

        $events = CommunityEvent::query()
            ->with('photo')
            ->where('audience', 'public')
            ->where('is_published', true)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get(['id', 'title', 'description', 'starts_at', ...(Schema::hasColumn('community_events', 'signatory_data') ? ['signatory_data'] : [])]);

        $financialTotals = FinancialEntry::query()
            ->where('public_summary', true)
            ->select('direction', DB::raw('SUM(amount) as total'))
            ->groupBy('direction')
            ->pluck('total', 'direction');

        $assetQuantity = DB::table('community_assets')
            ->where('public_summary', true)
            ->sum('quantity');

        return view('home', [
            'announcements' => $announcements,
            'events' => $events,
            'incomeTotal' => (int) ($financialTotals['income'] ?? 0),
            'expenseTotal' => (int) ($financialTotals['expense'] ?? 0),
            'assetQuantity' => (int) $assetQuantity,
        ]);
    }
}
