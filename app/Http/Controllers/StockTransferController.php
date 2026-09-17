<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreStockTransferRequest;
use App\Models\Godown;
use App\Models\StockTransfer;
use App\Models\TransferItem;
use App\Services\NumberGenerator;
use App\Services\PdfService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function __construct(private StockService $stockService, private PdfService $pdfService)
    {
    }

    public function index(Request $request)
    {
        $query = StockTransfer::with(['sourceGodown', 'destGodown', 'creator', 'items']);

        if ($request->filled('godown_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('source_godown_id', $request->godown_id)
                  ->orWhere('dest_godown_id', $request->godown_id);
            });
        }

        $transfers = $query->latest()->paginate(20)->withQueryString();
        $godowns = Godown::active()->get();

        return view('stock-transfers.index', compact('transfers', 'godowns'));
    }

    public function create()
    {
        $godowns = Godown::active()->get();

        return view('stock-transfers.create', compact('godowns'));
    }

    public function store(StoreStockTransferRequest $request)
    {
        try {
            $transfer = DB::transaction(function () use ($request) {
                $transferNumber = NumberGenerator::transfer();

                // Suppress the automatic Activity Log entry here — items are
                // added in the loop below, so logging now would only ever
                // capture the header, never which products are moving. One
                // complete entry is written manually once items exist.
                $transfer = StockTransfer::withoutEvents(fn () => StockTransfer::create([
                    'transfer_number' => $transferNumber,
                    'source_godown_id' => $request->source_godown_id,
                    'dest_godown_id' => $request->dest_godown_id,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                    'notes' => $request->notes,
                ]));

                foreach ($request->items as $item) {
                    TransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'sku_id' => $item['sku_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }

                $transfer->load(['items.sku', 'sourceGodown']);
                $this->stockService->processTransferOut($transfer);

                $transfer->logCreatedWithItems(['items' => $this->itemsSummary($transfer->items)]);

                return $transfer;
            });

            return redirect()->route('stock-transfers.show', $transfer)
                ->with('success', "Transfer {$transfer->transfer_number} created successfully.");
        } catch (InsufficientStockException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create transfer: ' . $e->getMessage());
        }
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['items.sku', 'sourceGodown', 'destGodown', 'creator', 'resolver']);
        return view('stock-transfers.show', compact('stockTransfer'));
    }

    public function downloadChallan(StockTransfer $stockTransfer)
    {
        return $this->pdfService->generateTransferChallanPdf($stockTransfer)
            ->download("{$stockTransfer->transfer_number}-challan.pdf");
    }

    /** "GIP-001 x 40, GIP-002 x 20" — a readable Activity Log summary. */
    private function itemsSummary($items): string
    {
        return $items->map(function ($item) {
            $qty = rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.');
            return "{$item->sku->code} x {$qty}";
        })->implode(', ');
    }

    public function accept(StockTransfer $stockTransfer)
    {
        try {
            DB::transaction(function () use ($stockTransfer) {
                $transfer = StockTransfer::lockForUpdate()->find($stockTransfer->id);

                if ($transfer->status !== 'pending') {
                    throw new \RuntimeException('This transfer has already been processed.');
                }

                $transfer->load(['items.sku']);
                $this->stockService->processTransferAccept($transfer);

                $transfer->update([
                    'status' => 'completed',
                    'resolved_by' => auth()->id(),
                    'resolved_at' => now(),
                ]);
            });

            return redirect()->route('stock-transfers.index')
                ->with('success', "Transfer {$stockTransfer->transfer_number} accepted.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to accept transfer: ' . $e->getMessage());
        }
    }

    public function reject(StockTransfer $stockTransfer)
    {
        try {
            DB::transaction(function () use ($stockTransfer) {
                $transfer = StockTransfer::lockForUpdate()->find($stockTransfer->id);

                if ($transfer->status !== 'pending') {
                    throw new \RuntimeException('This transfer has already been processed.');
                }

                $transfer->load(['items.sku', 'sourceGodown']);
                $this->stockService->processTransferReject($transfer);

                $transfer->update([
                    'status' => 'rejected',
                    'resolved_by' => auth()->id(),
                    'resolved_at' => now(),
                ]);
            });

            return redirect()->route('stock-transfers.index')
                ->with('success', "Transfer {$stockTransfer->transfer_number} rejected. Stock restored to source.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject transfer: ' . $e->getMessage());
        }
    }
}
