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

                $adjustment = StockAdjustment::create([
                    'adjustment_number' => $adjNumber,
                    'godown_id' => $request->godown_id,
                    'reason' => $request->reason,
                    'reason_notes' => $request->reason_notes,
                    'reference_doc' => $request->reference_doc,
                    'created_by' => auth()->id(),
                ]);

                foreach ($request->items as $item) {
                    AdjustmentItem::create([
                        'stock_adjustment_id' => $adjustment->id,
                        'sku_id' => $item['sku_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }

                $adjustment->load(['items.sku', 'godown']);
                $this->stockService->processAdjustment($adjustment);

                return $adjustment;
            });

            return redirect()->route('stock-adjustments.show', $adjustment)
                ->with('success', "Adjustment {$adjustment->adjustment_number} created successfully.");
        } catch (InsufficientStockException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create adjustment: ' . $e->getMessage());
        }
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        $stockAdjustment->load(['items.sku', 'godown', 'creator']);
        return view('stock-adjustments.show', compact('stockAdjustment'));
    }
}
