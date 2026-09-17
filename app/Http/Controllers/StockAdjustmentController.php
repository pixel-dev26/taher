<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Models\AdjustmentItem;
use App\Models\Godown;
use App\Models\StockAdjustment;
use App\Services\NumberGenerator;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    public function index(Request $request)
    {
        $query = StockAdjustment::with(['godown', 'creator', 'items']);

        if ($request->filled('godown_id')) {
            $query->where('godown_id', $request->godown_id);
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $adjustments = $query->latest()->paginate(20)->withQueryString();
        $godowns = Godown::active()->get();

        return view('stock-adjustments.index', compact('adjustments', 'godowns'));
    }

    public function create()
    {
        $godowns = Godown::active()->get();
        return view('stock-adjustments.create', compact('godowns'));
    }

    public function store(StoreStockAdjustmentRequest $request)
    {
        try {
            $adjustment = DB::transaction(function () use ($request) {
                $adjNumber = NumberGenerator::adjustment();

                // Suppress the automatic Activity Log entry here — items are
                // added in the loop below, so logging now would only ever
                // capture the header, never which products were adjusted.
                // One complete entry is written manually once items exist.
                $adjustment = StockAdjustment::withoutEvents(fn () => StockAdjustment::create([
                    'adjustment_number' => $adjNumber,
                    'godown_id' => $request->godown_id,
                    'reason' => $request->reason,
                    'reason_notes' => $request->reason_notes,
                    'reference_doc' => $request->reference_doc,
                    'created_by' => auth()->id(),
                ]));

                foreach ($request->items as $item) {
                    AdjustmentItem::create([
                        'stock_adjustment_id' => $adjustment->id,
                        'sku_id' => $item['sku_id'],
                        'quantity' => $item['quantity'],
                        // Only stock added carries a price; a removal's price box is ignored.
                        'unit_price' => (float) $item['quantity'] > 0 ? ($item['unit_price'] ?? null) : null,
                    ]);
                }

                $adjustment->load(['items.sku', 'godown']);
                $this->stockService->processAdjustment($adjustment);

                $adjustment->logCreatedWithItems(['items' => $this->itemsSummary($adjustment->items)]);

                return $adjustment;
            });

            return redirect()->route('stock-adjustments.show', $adjustment)
                ->with('success', "Adjustment {$adjustment->adjustment_number} created successfully.");
        } catch (InsufficientStockException | \RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('error', 'Failed to create the adjustment. Please try again; if it keeps happening, contact your administrator.');
        }
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        $stockAdjustment->load(['items.sku', 'godown', 'creator']);
        return view('stock-adjustments.show', compact('stockAdjustment'));
    }

    /** "GIP-001 x +40, GIP-002 x -5" — a readable Activity Log summary. */
    private function itemsSummary($items): string
    {
        return $items->map(function ($item) {
            $qty = (float) $item->quantity;
            $formatted = rtrim(rtrim(number_format(abs($qty), 3, '.', ''), '0'), '.');
            return "{$item->sku->code} x " . ($qty >= 0 ? '+' : '-') . $formatted;
        })->implode(', ');
    }
}
