<?php

namespace App\Http\Controllers;

use App\Models\DispatchSheet;
use App\Models\Godown;
use App\Models\Grn;
use App\Models\Sku;
use App\Models\StockLedger;
use App\Models\StockRecord;
use App\Models\StockTransfer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalSkus = Sku::active()->count();
        $totalGodowns = Godown::active()->count();

        $godowns = Godown::active()->get();
        $stockSummary = [];
        foreach ($godowns as $godown) {
            $records = StockRecord::where('godown_id', $godown->id)->get();
            $stockSummary[] = [
                'godown' => $godown,
                'on_hand' => $records->sum('on_hand'),
                'reserved' => $records->sum('reserved'),
                'available' => $records->sum(fn($r) => (float)$r->on_hand - (float)$r->reserved),
            ];
        }

        $recentActivity = StockLedger::with(['sku', 'godown', 'performer'])
            ->latest('created_at')
            ->limit(6)
            ->get();

        // 7-day stock in/out trend
        $ledgerEntries = StockLedger::where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->whereIn('movement_type', ['stock_in', 'dispatch_out'])
            ->get(['created_at', 'movement_type', 'quantity']);

        $stockTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $dayKey = $day->format('Y-m-d');
            $dayEntries = $ledgerEntries->filter(fn($e) => $e->created_at->format('Y-m-d') === $dayKey);
            $stockTrend[] = [
                'label' => $day->format('D'),
                'in' => (float) $dayEntries->where('movement_type', 'stock_in')->sum('quantity'),
                'out' => (float) abs($dayEntries->where('movement_type', 'dispatch_out')->sum('quantity')),
            ];
        }

        // Stock health breakdown across active SKUs
        $activeSkus = Sku::active()->get(['id', 'code', 'name', 'unit_of_measure', 'low_stock_threshold']);
        $recordsBySku = StockRecord::whereIn('sku_id', $activeSkus->pluck('id'))
            ->get(['sku_id', 'on_hand', 'reserved'])
            ->groupBy('sku_id');

        $stockHealth = ['good' => 0, 'low' => 0, 'critical' => 0, 'zero' => 0];
        $lowStockSkus = collect();
        foreach ($activeSkus as $sku) {
            $available = ($recordsBySku->get($sku->id) ?? collect())
                ->sum(fn($r) => (float)$r->on_hand - (float)$r->reserved);

            if ($available <= 0) {
                $stockHealth['zero']++;
            } elseif ($available <= $sku->low_stock_threshold * 0.5) {
                $stockHealth['critical']++;
                $lowStockSkus->push(['sku' => $sku, 'available' => $available]);
            } elseif ($available <= $sku->low_stock_threshold) {
                $stockHealth['low']++;
                $lowStockSkus->push(['sku' => $sku, 'available' => $available]);
            } else {
                $stockHealth['good']++;
            }
        }
        $lowStockSkus = $lowStockSkus->sortBy('available')->take(10)->values();

        // Top moving SKUs by total ledger activity
        $topMovers = StockLedger::selectRaw('sku_id, SUM(ABS(quantity)) as total_movement')
            ->whereIn('movement_type', ['stock_in', 'dispatch_out', 'transfer_out', 'transfer_in'])
            ->groupBy('sku_id')
            ->orderByDesc('total_movement')
            ->with('sku')
            ->limit(5)
            ->get();

        // Pending dispatches waiting to be sent out (across all godowns)
        $pendingDispatches = DispatchSheet::pending()
            ->with(['godown'])
            ->orderBy('delivery_date')
            ->orderBy('created_at')
            ->limit(8)
            ->get();
        $pendingDispatchCount = DispatchSheet::pending()->count();

        // Incoming transfers waiting to be accepted (across all godowns)
        $incomingTransfers = StockTransfer::pending()
            ->with(['sourceGodown', 'destGodown', 'creator'])
            ->latest()
            ->limit(8)
            ->get();

        $todayGrns = Grn::whereDate('created_at', today())->count();
        $todayDispatches = DispatchSheet::whereDate('dispatched_at', today())->dispatched()->count();

        return view('dashboard.index', compact(
            'totalSkus', 'totalGodowns', 'stockSummary', 'recentActivity',
            'stockTrend', 'stockHealth', 'topMovers', 'lowStockSkus',
            'pendingDispatches', 'pendingDispatchCount', 'incomingTransfers',
            'todayGrns', 'todayDispatches'
        ));
    }
}
