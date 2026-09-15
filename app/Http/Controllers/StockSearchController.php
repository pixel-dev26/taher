<?php

namespace App\Http\Controllers;

use App\Models\Godown;
use App\Models\Sku;
use App\Models\StockRecord;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockSearchController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    /**
     * The single Stock screen.
     *
     * Absorbs the old stock-as-on-date report: with no date it shows live
     * figures, and with a past date it reconstructs the position from the
     * ledger. Both used to be separate screens answering the same question.
     */
    public function index(Request $request)
    {
        $godowns = Godown::active()->get();
        $categories = Sku::distinct()->pluck('category')->sort();

        $today = today()->format('Y-m-d');
        $date = $request->get('date') ?: $today;
        $isToday = $date === $today;
        $godownId = $request->get('godown_id');
        $hideZero = $request->boolean('hide_zero');

        $query = Sku::active();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Hiding empty rows has to happen before paging, or pages come out
        // uneven. It is cheap: one lookup against the live balance table.
        if ($hideZero) {
            $query->whereIn('id', $this->skuIdsWithStock($godownId));
        }

        $skus = $query->orderBy('code')->paginate(50)->withQueryString();

        $columns = $godownId ? $godowns->where('id', $godownId) : $godowns;

        $figures = $this->stockService->stockFigures($date, $skus->pluck('id')->all());

        return view('stock-search.index', compact(
            'skus', 'godowns', 'columns', 'categories', 'figures',
            'date', 'isToday', 'godownId', 'hideZero'
        ));
    }

    /** SKU ids currently holding stock, optionally within one godown. */
    private function skuIdsWithStock(?string $godownId)
    {
        return StockRecord::when($godownId, fn ($q) => $q->where('godown_id', $godownId))
            ->where(fn ($q) => $q->where('on_hand', '>', 0)->orWhere('reserved', '>', 0))
            ->distinct()
            ->pluck('sku_id');
    }

    public function apiGetAvailability(int $sku, int $godown)
    {
        return response()->json([
            'available' => $this->stockService->getAvailable($sku, $godown),
        ]);
    }
}
