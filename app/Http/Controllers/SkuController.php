<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSkuRequest;
use App\Http\Requests\UpdateSkuRequest;
use App\Models\Godown;
use App\Models\GrnItem;
use App\Models\Sku;
use App\Models\StockRecord;
use App\Services\StockService;
use Illuminate\Http\Request;

class SkuController extends Controller
{
    public function index(Request $request)
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

        return view('skus.index', compact('skus', 'categories'));
    }

    public function create()
    {
        $categories = Sku::distinct()->pluck('category')->sort();
        return view('skus.create', compact('categories'));
    }

    public function store(StoreSkuRequest $request)
    {
        $data = $request->validated();

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

        return redirect()->route('skus.index')->with('success', 'SKU created successfully.');
    }

    public function show(Sku $sku, StockService $stockService)
    {
        $stockRecords = StockRecord::where('sku_id', $sku->id)
            ->with('godown')
            ->get();

        $price = $stockService->averagePrices([$sku->id])[$sku->id] ?? null;

        // The receipts behind the average, newest first.
        $purchases = GrnItem::select('grn_items.*')
            ->join('grns', 'grns.id', '=', 'grn_items.grn_id')
            ->where('grn_items.sku_id', $sku->id)
            ->whereNotNull('grn_items.unit_price')
            ->with('grn.godown')
            ->orderByDesc('grns.receipt_date')
            ->orderByDesc('grn_items.id')
            ->limit(10)
            ->get();

        return view('skus.show', compact('sku', 'stockRecords', 'price', 'purchases'));
    }

    public function edit(Sku $sku)
    {
        $categories = Sku::distinct()->pluck('category')->sort();
        return view('skus.edit', compact('sku', 'categories'));
    }

    public function update(UpdateSkuRequest $request, Sku $sku)
    {
        $data = $request->validated();

        if (isset($data['variant_attributes'])) {
            $attrs = [];
            foreach ($data['variant_attributes'] as $attr) {
                if (!empty($attr['key'])) {
                    $attrs[$attr['key']] = $attr['value'];
                }
            }
            $data['variant_attributes'] = !empty($attrs) ? $attrs : null;
        }

        $sku->update($data);

        return redirect()->route('skus.index')->with('success', 'SKU updated successfully.');
    }

    public function destroy(Sku $sku)
    {
        // Check if SKU has any stock
        $hasStock = StockRecord::where('sku_id', $sku->id)
            ->where(function ($q) {
                $q->where('on_hand', '>', 0)->orWhere('reserved', '>', 0);
            })->exists();

        if ($hasStock) {
            return back()->with('error', 'Cannot delete SKU with existing stock. Deactivate it instead.');
        }

        $sku->update(['is_active' => false]);
        return redirect()->route('skus.index')->with('success', 'SKU deactivated.');
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
