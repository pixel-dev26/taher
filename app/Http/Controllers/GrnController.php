<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGrnRequest;
use App\Models\Godown;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Services\NumberGenerator;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrnController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    public function index(Request $request)
    {
        $query = Grn::with(['creator', 'items', 'godown']);

        if ($request->filled('search')) {
            // The field offers to search by supplier too, so honour that.
            $term = "%{$request->search}%";
            $query->where(function ($q) use ($term) {
                $q->where('grn_number', 'like', $term)
                  ->orWhere('supplier_name', 'like', $term)
                  ->orWhere('challan_no', 'like', $term);
            });
        }

        if ($request->filled('godown_id')) {
            $query->where('godown_id', $request->godown_id);
        }

        $grns = $query->latest('receipt_date')->latest()->paginate(20)->withQueryString();
        $godowns = Godown::active()->get();

        return view('grn.index', compact('grns', 'godowns'));
    }

    public function create()
    {
        $godowns = Godown::active()->get();
        return view('grn.create', compact('godowns'));
    }

    public function store(StoreGrnRequest $request)
    {
        try {
            $grn = DB::transaction(function () use ($request) {
                $grnNumber = NumberGenerator::grn();

                $grn = Grn::create([
                    'grn_number' => $grnNumber,
                    'godown_id' => $request->godown_id,
                    'receipt_date' => $request->receipt_date,
                    'challan_no' => $request->challan_no,
                    'supplier_name' => $request->supplier_name,
                    'notes' => $request->notes,
                    'created_by' => auth()->id(),
                ]);

                foreach ($request->items as $item) {
                    GrnItem::create([
                        'grn_id' => $grn->id,
                        'sku_id' => $item['sku_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }

                $grn->load('items');
                $this->stockService->processStockIn($grn);

                return $grn;
            });

            return redirect()->route('grn.show', $grn)->with('success', "GRN {$grn->grn_number} created successfully.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create GRN: ' . $e->getMessage());
        }
    }

    public function show(Grn $grn)
    {
        $grn->load(['items.sku', 'godown', 'creator']);
        return view('grn.show', compact('grn'));
    }
}
