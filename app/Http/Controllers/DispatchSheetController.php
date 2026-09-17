<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreDispatchSheetRequest;
use App\Http\Requests\UpdateDispatchSheetRequest;
use App\Models\DispatchSheet;
use App\Models\DispatchSheetItem;
use App\Models\Godown;
use App\Models\Sku;
use App\Services\NumberGenerator;
use App\Services\PdfService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DispatchSheetController extends Controller
{
    public function __construct(
        private StockService $stockService,
        private PdfService $pdfService
    ) {
    }

    /**
     * The single Dispatch screen.
     *
     * Absorbs the fulfilment queue and the dispatch register, which were three
     * separate screens over one table. Each tab keeps the exact query its old
     * screen used, so no capability is lost:
     *   to-send  every pending sheet, oldest delivery first (was /fulfillment)
     *   mine     sheets this user created, any status (was this screen)
     *   history  everything, with date/godown filters and exports
     *            (was /reports/dispatch-register)
     */
    public function index(Request $request)
    {
        $tab = in_array($request->get('tab'), ['to-send', 'mine', 'history'], true)
            ? $request->get('tab')
            : 'to-send';

        $godowns = Godown::active()->get();

        $query = DispatchSheet::with(['godown', 'creator', 'items.sku']);

        if ($tab === 'to-send') {
            $query->pending()->orderBy('delivery_date')->orderBy('created_at');
        } else {
            if ($tab === 'mine') {
                $query->where('created_by', auth()->id());
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('godown_id')) {
                $query->where('godown_id', $request->godown_id);
            }

            // The register used to render blank until both dates were given.
            // Default to the last 30 days so the tab always shows something.
            $from = $request->get('date_from', $tab === 'history' ? today()->subDays(30)->format('Y-m-d') : null);
            $to = $request->get('date_to');

            if ($from) {
                $query->whereDate('created_at', '>=', $from);
            }

            if ($to) {
                $query->whereDate('created_at', '<=', $to);
            }

            $query->latest();
        }

        $sheets = $query->paginate($tab === 'history' ? 50 : 20)->withQueryString();

        $pendingCount = DispatchSheet::pending()->count();

        return view('dispatch-sheets.index', compact('sheets', 'tab', 'godowns', 'pendingCount'));
    }

    public function create()
    {
        $godowns = Godown::active()->get();
        return view('dispatch-sheets.create', compact('godowns'));
    }

    public function store(StoreDispatchSheetRequest $request)
    {
        try {
            $sheet = DB::transaction(function () use ($request) {
                $dsNumber = NumberGenerator::dispatchSheet();

                // Suppress the automatic Activity Log entry here — items are
                // added in the loop below, so logging now would only ever
                // capture the header, never which products are being sent.
                // One complete entry is written manually once items exist.
                // pdf_path is set to its final value up front (same pattern
                // PdfService uses) so that call's own ->update() further down
                // finds nothing dirty and doesn't add a second, spurious
                // "updated" entry for a path that isn't actually changing.
                $sheet = DispatchSheet::withoutEvents(fn () => DispatchSheet::create([
                    'ds_number' => $dsNumber,
                    'godown_id' => $request->godown_id,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $request->customer_phone,
                    'customer_gstin' => $request->customer_gstin,
                    'place_of_supply' => $request->place_of_supply,
                    'delivery_address' => $request->delivery_address,
                    'delivery_date' => $request->delivery_date,
                    'vehicle_no' => $request->vehicle_no,
                    'driver_name' => $request->driver_name,
                    'driver_phone' => $request->driver_phone,
                    'lr_no' => $request->lr_no,
                    'eway_no' => $request->eway_no,
                    'transport_name' => $request->transport_name,
                    'transport_id' => $request->transport_id,
                    'notes' => $request->notes,
                    'pdf_path' => "dispatch-sheets/{$dsNumber}.pdf",
                ]));

                foreach ($request->items as $item) {
                    DispatchSheetItem::create([
                        'dispatch_sheet_id' => $sheet->id,
                        'sku_id' => $item['sku_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'hsn_code' => $item['hsn_code'],
                    ]);
                    $this->backfillHsnCode($item['sku_id'], $item['hsn_code']);
                }

                $sheet->load(['items.sku', 'godown']);
                $this->stockService->reserveStock($sheet);

                // Generate PDF
                $this->pdfService->generateDispatchPdf($sheet);

                $sheet->logCreatedWithItems(['items' => $this->itemsSummary($sheet->items)]);

                return $sheet;
            });

            return redirect()->route('dispatch-sheets.show', $sheet)
                ->with('success', "Dispatch Sheet {$sheet->ds_number} created successfully.");
        } catch (InsufficientStockException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create dispatch sheet: ' . $e->getMessage());
        }
    }

    public function show(DispatchSheet $dispatchSheet)
    {
        $dispatchSheet->load(['items.sku', 'godown', 'creator', 'dispatcher']);
        return view('dispatch-sheets.show', compact('dispatchSheet'));
    }

    public function edit(DispatchSheet $dispatchSheet)
    {
        if ($dispatchSheet->status !== 'pending') {
            abort(403, 'Only pending dispatch sheets can be edited.');
        }

        $dispatchSheet->load(['items.sku']);
        $godowns = Godown::active()->get();

        return view('dispatch-sheets.edit', compact('dispatchSheet', 'godowns'));
    }

    public function update(UpdateDispatchSheetRequest $request, DispatchSheet $dispatchSheet)
    {
        try {
            DB::transaction(function () use ($request, $dispatchSheet) {
                $headerFields = [
                    'customer_name', 'customer_phone', 'customer_gstin', 'place_of_supply',
                    'delivery_address', 'delivery_date', 'vehicle_no', 'driver_name',
                    'driver_phone', 'lr_no', 'eway_no', 'transport_name', 'transport_id', 'notes',
                ];

                $oldItems = $dispatchSheet->items->pluck('quantity', 'sku_id')
                    ->map(fn($q) => (float)$q)->toArray();

                // Snapshot before the edit, for one manual Activity Log entry
                // covering both header fields and items together — items are
                // swapped out below (delete + recreate), which the automatic
                // 'updated' hook can't see since it fires against the header
                // update alone, before the item swap happens. delivery_date
                // is cast to a Carbon instance, so it's normalized to a plain
                // string here — otherwise two fresh instances of the same
                // unchanged date would never compare equal, and logChange()'s
                // "skip when nothing changed" check would never trigger.
                $before = array_map([$this, 'normalizeForLog'], $dispatchSheet->only($headerFields));
                $before['items'] = $this->itemsSummary($dispatchSheet->items()->with('sku')->get());

                // Suppress the automatic entry for this header-only update —
                // it would otherwise log just the header fields under its own
                // separate, incomplete entry.
                DispatchSheet::withoutEvents(fn () => $dispatchSheet->update([
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $request->customer_phone,
                    'customer_gstin' => $request->customer_gstin,
                    'place_of_supply' => $request->place_of_supply,
                    'delivery_address' => $request->delivery_address,
                    'delivery_date' => $request->delivery_date,
                    'vehicle_no' => $request->vehicle_no,
                    'driver_name' => $request->driver_name,
                    'driver_phone' => $request->driver_phone,
                    'lr_no' => $request->lr_no,
                    'eway_no' => $request->eway_no,
                    'transport_name' => $request->transport_name,
                    'transport_id' => $request->transport_id,
                    'notes' => $request->notes,
                ]));

                // Delete old items and create new ones
                $dispatchSheet->items()->delete();
                foreach ($request->items as $item) {
                    DispatchSheetItem::create([
                        'dispatch_sheet_id' => $dispatchSheet->id,
                        'sku_id' => $item['sku_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'hsn_code' => $item['hsn_code'],
                    ]);
                    $this->backfillHsnCode($item['sku_id'], $item['hsn_code']);
                }

                $newItems = collect($request->items)->pluck('quantity', 'sku_id')
                    ->map(fn($q) => (float)$q)->toArray();

                $dispatchSheet->load(['items.sku', 'godown']);
                $this->stockService->updateReservation($dispatchSheet, $oldItems, $newItems);

                // Regenerate PDF
                $this->pdfService->generateDispatchPdf($dispatchSheet);

                $after = array_map([$this, 'normalizeForLog'], $dispatchSheet->only($headerFields));
                $after['items'] = $this->itemsSummary($dispatchSheet->items);
                $dispatchSheet->logChange('updated', $before, $after);
            });

            return redirect()->route('dispatch-sheets.show', $dispatchSheet)
                ->with('success', 'Dispatch sheet updated successfully.');
        } catch (InsufficientStockException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    public function cancel(Request $request, DispatchSheet $dispatchSheet)
    {
        if ($dispatchSheet->status !== 'pending') {
            return back()->with('error', 'Only pending dispatch sheets can be cancelled.');
        }

        $request->validate(['cancel_reason' => 'required|string|max:1000']);

        try {
            DB::transaction(function () use ($request, $dispatchSheet) {
                $dispatchSheet->load('items.sku');
                $this->stockService->releaseStock($dispatchSheet);

                $dispatchSheet->update([
                    'status' => 'cancelled',
                    'cancel_reason' => $request->cancel_reason,
                    'cancelled_at' => now(),
                ]);
            });

            return redirect()->route('dispatch-sheets.show', $dispatchSheet)
                ->with('success', 'Dispatch sheet cancelled. Stock has been released.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to cancel: ' . $e->getMessage());
        }
    }

    public function downloadPdf(DispatchSheet $dispatchSheet)
    {
        // Always regenerate rather than reusing the stored file: the sheet's
        // status, dispatch timestamp, or cancellation reason can all change
        // after the PDF was first generated (on create/edit), and a cached
        // file would otherwise go stale — e.g. still showing "Pending" on a
        // sheet that has since been sent out or cancelled.
        $this->pdfService->generateDispatchPdf($dispatchSheet);
        $dispatchSheet->refresh();

        if (!Storage::disk('public')->exists($dispatchSheet->pdf_path)) {
            abort(404, 'PDF not found.');
        }

        return Storage::disk('public')->download($dispatchSheet->pdf_path, "{$dispatchSheet->ds_number}.pdf");
    }

    /**
     * A GST-compliant Road/Delivery Challan for this dispatch — a separate
     * document from the plain Dispatch Sheet PDF above, generated fresh on
     * every download rather than cached to disk.
     */
    public function downloadChallan(DispatchSheet $dispatchSheet)
    {
        return $this->pdfService->generateChallanPdf($dispatchSheet)
            ->download("{$dispatchSheet->ds_number}-challan.pdf");
    }

    /**
     * The Products catalog is the long-term home for a SKU's HSN code, but
     * most don't have one set yet — this fills it in from whatever was first
     * typed on a dispatch, so the same product doesn't need retyping on the
     * next one. Never overwrites a value the Products screen already has.
     */
    private function backfillHsnCode(int $skuId, string $hsnCode): void
    {
        $sku = Sku::find($skuId);

        if ($sku && !$sku->hsn_code) {
            $sku->update(['hsn_code' => $hsnCode]);
        }
    }

    /** "GIP-001 x 40, GIP-002 x 20" — a readable Activity Log summary. */
    private function itemsSummary($items): string
    {
        return collect($items)->map(function ($item) {
            $qty = rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.');
            return "{$item->sku->code} x {$qty}";
        })->implode(', ');
    }

    /** Carbon casts (delivery_date) need to be plain strings to compare/log cleanly. */
    private function normalizeForLog($value)
    {
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
    }
}
