<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SanitizesFilters;
use App\Models\Godown;
use App\Models\Sku;
use App\Models\StockRecord;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockSearchController extends Controller
{
    use SanitizesFilters;

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

        // A date the calendar can't parse (a typo in a bookmarked URL) used
        // to reach Carbon::parse() in the view and 500; fall back to today.
        $valid = $this->filters($request, [
            'date' => 'nullable|date_format:Y-m-d',
            'godown_id' => 'nullable|integer|exists:godowns,id',
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'hide_zero' => 'nullable|boolean',
        ]);

        $today = today()->format('Y-m-d');
        $date = $valid['date'] ?? $today;
        $isToday = $date === $today;
        $godownId = $valid['godown_id'] ?? null;
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
        $prices = $this->stockService->averagePrices($skus->pluck('id')->all(), $date);

        return view('stock-search.index', compact(
            'skus', 'godowns', 'columns', 'categories', 'figures', 'prices',
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
