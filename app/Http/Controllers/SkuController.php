<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSkuRequest;
use App\Http\Requests\UpdateSkuRequest;
use App\Models\AdjustmentItem;
use App\Models\Godown;
use App\Models\Sku;
use App\Models\StockAdjustment;
use App\Models\StockRecord;
use App\Services\NumberGenerator;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SkuController extends Controller
{
    public function index(Request $request, StockService $stockService)
    {
        $query = Sku::query();

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

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $skus = $query->latest()->paginate(20)->withQueryString();
        $categories = Sku::distinct()->pluck('category')->sort();
        $prices = $stockService->averagePrices($skus->pluck('id')->all());

        return view('skus.index', compact('skus', 'categories', 'prices'));
    }

    public function create()
    {
        $categories = Sku::distinct()->pluck('category')->sort();
        $godowns = Godown::active()->get();
        return view('skus.create', compact('categories', 'godowns'));
    }

    public function store(StoreSkuRequest $request, StockService $stockService)
    {
        $data = $request->validated();

        // Left blank, these fall back to the column's own database default
        // ('Other', 'Pcs', 10) instead of saving an empty string.
        foreach (['category', 'unit_of_measure', 'low_stock_threshold', 'price'] as $field) {
            if (($data[$field] ?? null) === null || $data[$field] === '') {
                unset($data[$field]);
            }
        }

        // Not Sku columns — pulled out before create() and used below instead.
        $targetGodownId = $data['target_godown_id'] ?? null;
        $openingQuantity = $data['opening_quantity'] ?? null;
        unset($data['target_godown_id'], $data['opening_quantity']);

        // Handle variant attributes
        if (isset($data['variant_attributes'])) {
            $attrs = [];
            foreach ($data['variant_attributes'] as $attr) {
                if (!empty($attr['key'])) {
                    $attrs[$attr['key']] = $attr['value'];
                }
            }
            $data['variant_attributes'] = !empty($attrs) ? $attrs : null;
        }

        DB::transaction(function () use ($data, $targetGodownId, $openingQuantity, $stockService) {
            $sku = Sku::create($data);

            // Create stock records for all active godowns
            $godowns = Godown::active()->get();
            foreach ($godowns as $godown) {
                StockRecord::create([
                    'sku_id' => $sku->id,
                    'godown_id' => $godown->id,
                    'on_hand' => 0,
                    'reserved' => 0,
                ]);
            }

            // An opening quantity at one godown is recorded the same way any
            // other stock addition is — a real, auditable Stock Adjustment
            // (reason: initial_load) — rather than writing to StockRecord
            // directly, so it appears in the ledger and Activity Log like
            // any other stock movement. unit_price is left null: it falls
            // back to the product's own price via averagePrices()'s
            // COALESCE, the same as any other unpriced opening stock.
            if ($targetGodownId && $openingQuantity) {
                $adjustment = StockAdjustment::withoutEvents(fn () => StockAdjustment::create([
                    'adjustment_number' => NumberGenerator::adjustment(),
                    'godown_id' => $targetGodownId,
                    'reason' => 'initial_load',
                    'reason_notes' => "Opening stock entered while adding {$sku->code}.",
                    'created_by' => auth()->id(),
                ]));

                AdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'sku_id' => $sku->id,
                    'quantity' => $openingQuantity,
                    'unit_price' => null,
                ]);

                $adjustment->load(['items.sku', 'godown']);
                $stockService->processAdjustment($adjustment);

                $qty = rtrim(rtrim(number_format((float) $openingQuantity, 3, '.', ''), '0'), '.');
                $adjustment->logCreatedWithItems(['items' => "{$sku->code} x +{$qty}"]);
            }
        });

        return redirect()->route('skus.index')->with('success', 'SKU created successfully.');
    }

    public function show(Sku $sku, StockService $stockService)
    {
        $stockRecords = StockRecord::where('sku_id', $sku->id)
            ->with('godown')
            ->get();

        $price = $stockService->averagePrices([$sku->id])[$sku->id] ?? null;

        // The stock-in lines behind the average, newest first.
        $purchases = $stockService->stockInLines([$sku->id])
            ->orderByDesc('moved_at')
            ->orderByDesc('line_id')
            ->limit(10)
            ->get();
        $purchaseCount = $stockService->stockInLines([$sku->id])->count();
        $godownCodes = Godown::pluck('code', 'id');

        return view('skus.show', compact('sku', 'stockRecords', 'price', 'purchases', 'purchaseCount', 'godownCodes'));
    }

    public function edit(Sku $sku)
    {
        $categories = Sku::distinct()->pluck('category')->sort();
        return view('skus.edit', compact('sku', 'categories'));
    }

    public function update(UpdateSkuRequest $request, Sku $sku)
    {
        $data = $request->validated();

        // Left blank, these keep their current value instead of being wiped.
        foreach (['category', 'unit_of_measure', 'low_stock_threshold', 'price', 'weight'] as $field) {
            if (($data[$field] ?? null) === null || $data[$field] === '') {
                unset($data[$field]);
            }
        }

        // The secondary unit follows the same "blank = unchanged" rule, with
        // one explicit override: the edit form's "Remove" action sets this
        // hidden flag to actually clear it back to a single-unit product.
        if ($request->boolean('clear_secondary_unit')) {
            $data['secondary_unit_of_measure'] = null;
            $data['conversion_rate'] = null;
        } else {
            foreach (['secondary_unit_of_measure', 'conversion_rate'] as $field) {
                if (($data[$field] ?? null) === null || $data[$field] === '') {
                    unset($data[$field]);
                }
            }
        }

        if (isset($data['variant_attributes'])) {
            $attrs = [];
            foreach ($data['variant_attributes'] as $attr) {
                if (!empty($attr['key'])) {
                    $attrs[$attr['key']] = $attr['value'];
                }
            }
            $data['variant_attributes'] = !empty($attrs) ? $attrs : null;
        }

        $deactivating = array_key_exists('is_active', $data) && ! $data['is_active'] && $sku->is_active;

        if ($deactivating && $this->hasStock($sku)) {
            return back()->withInput()->with('error', 'Cannot deactivate a product that still has stock. Move or correct the stock out first.');
        }

        $sku->update($data);

        return redirect()->route('skus.index')->with('success', 'SKU updated successfully.');
    }

    public function destroy(Sku $sku)
    {
        if ($this->hasStock($sku)) {
            return back()->with('error', 'Cannot deactivate a product that still has stock. Move or correct the stock out first.');
        }

        $sku->update(['is_active' => false]);
        return redirect()->route('skus.index')->with('success', 'SKU deactivated.');
    }

    private function hasStock(Sku $sku): bool
    {
        return StockRecord::where('sku_id', $sku->id)
            ->where(function ($q) {
                $q->where('on_hand', '>', 0)->orWhere('reserved', '>', 0);
            })->exists();
    }

    public function apiSearch(Request $request)
    {
        $query = $request->get('q', '');
        $godownId = $request->get('godown_id');
        $limit = 20;

        $matches = Sku::active()
            ->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                  ->orWhere('name', 'like', "%{$query}%");
            });

        // Total before the limit, so the picker can say "showing 20 of 152".
        $total = (clone $matches)->count();

        $skus = $matches->orderBy('code')->limit($limit)->get();

        // Resolve availability for the whole page in one query instead of one per SKU.
        $records = $godownId
            ? StockRecord::whereIn('sku_id', $skus->pluck('id'))
                ->where('godown_id', $godownId)
                ->get()
                ->keyBy('sku_id')
            : collect();

        $items = $skus->map(function ($sku) use ($godownId, $records) {
            $data = [
                'id' => $sku->id,
                'code' => $sku->code,
                'name' => $sku->name,
                'uom' => $sku->unit_of_measure,
                'category' => $sku->category,
                'hsn_code' => $sku->hsn_code,
                'secondary_uom' => $sku->hasSecondaryUnit() ? $sku->secondary_unit_of_measure : null,
                'conversion_rate' => $sku->hasSecondaryUnit() ? (float) $sku->conversion_rate : null,
            ];

            if ($godownId) {
                $record = $records->get($sku->id);
                $data['available'] = $record ? (float)$record->on_hand - (float)$record->reserved : 0;
            }

            return $data;
        });

        return response()->json([
            'items' => $items,
            'total' => $total,
            'limit' => $limit,
        ]);
    }
}
