<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Concerns\SanitizesFilters;
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
use Throwable;

class DispatchSheetController extends Controller
{
    use SanitizesFilters;

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
        $filters = $this->filters($request, [
            'tab' => 'nullable|string|in:to-send,mine,history',
            'status' => 'nullable|string|in:pending,dispatched,cancelled',
            'godown_id' => 'nullable|integer|exists:godowns,id',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
        ]);

        $tab = $filters['tab'] ?? 'to-send';

        $godowns = Godown::active()->get();

        $query = DispatchSheet::with(['godown', 'creator', 'items.sku']);

        if ($tab === 'to-send') {
            $query->pending()->orderBy('delivery_date')->orderBy('created_at');
        } else {
            if ($tab === 'mine') {
                $query->where('created_by', auth()->id());
            }

            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (! empty($filters['godown_id'])) {
                $query->where('godown_id', $filters['godown_id']);
            }

            // The register used to render blank until both dates were given.
            // Default to the last 30 days so the tab always shows something.
            $from = $filters['date_from'] ?? ($tab === 'history' ? today()->subDays(30)->format('Y-m-d') : null);
            $to = $filters['date_to'] ?? null;

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

                $sheet->logCreatedWithItems(['items' => $this->itemsSummary($sheet->items)]);

                return $sheet;
            });

            return redirect()->route('dispatch-sheets.show', $sheet)
                ->with('success', "Dispatch Sheet {$sheet->ds_number} created successfully.");
        } catch (InsufficientStockException | \RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            return back()->withInput()->with('error', 'Failed to create the dispatch sheet. Please try again; if it keeps happening, contact your administrator.');
        }
    }

    public function show(DispatchSheet $dispatchSheet)
    {
        $dispatchSheet->load(['items.sku', 'godown', 'creator', 'dispatcher']);
        $challanIssue = $this->pdfService->challanBlocker($dispatchSheet);

        return view('dispatch-sheets.show', compact('dispatchSheet', 'challanIssue'));
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
                // Re-read under lock. The FormRequest already checked the
                // status, but on the instance route binding loaded before the
                // transaction — a confirm landing in between would otherwise
                // let this edit rewrite a sheet that has already gone out and
                // leave its extra reservation stuck forever.
                $sheet = DispatchSheet::lockForUpdate()->find($dispatchSheet->id);

                if (! $sheet || $sheet->status !== 'pending') {
                    throw new \RuntimeException('This dispatch sheet has already been processed and can no longer be edited.');
                }

                $headerFields = [
                    'customer_name', 'customer_phone', 'customer_gstin', 'place_of_supply',
                    'delivery_address', 'delivery_date', 'vehicle_no', 'driver_name',
                    'driver_phone', 'lr_no', 'eway_no', 'transport_name', 'transport_id', 'notes',
                ];

                $sheet->load(['items.sku', 'godown']);

                $oldItems = $sheet->items->pluck('quantity', 'sku_id')
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
                $before = array_map([$this, 'normalizeForLog'], $sheet->only($headerFields));
                $before['items'] = $this->itemsSummary($sheet->items);

                // Suppress the automatic entry for this header-only update —
                // it would otherwise log just the header fields under its own
                // separate, incomplete entry.
                DispatchSheet::withoutEvents(fn () => $sheet->update([
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
                $sheet->items()->delete();
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

                $newItems = collect($request->items)->pluck('quantity', 'sku_id')
                    ->map(fn($q) => (float)$q)->toArray();

                $sheet->load(['items.sku', 'godown']);
                $this->stockService->updateReservation($sheet, $oldItems, $newItems);

                $after = array_map([$this, 'normalizeForLog'], $sheet->only($headerFields));
                $after['items'] = $this->itemsSummary($sheet->items);
                $sheet->logChange('updated', $before, $after);
            });

            return redirect()->route('dispatch-sheets.show', $dispatchSheet)
                ->with('success', 'Dispatch sheet updated successfully.');
        } catch (InsufficientStockException | \RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            return back()->withInput()->with('error', 'Failed to update the dispatch sheet. Please try again; if it keeps happening, contact your administrator.');
        }
    }

    public function cancel(Request $request, DispatchSheet $dispatchSheet)
    {
        $request->validate(['cancel_reason' => 'required|string|max:1000']);

        try {
            DB::transaction(function () use ($request, $dispatchSheet) {
                // Re-read under lock (see update()): cancelling a sheet that
                // was confirmed a moment ago would release a reservation that
                // no longer exists and eat another sheet's reserved stock.
                $sheet = DispatchSheet::lockForUpdate()->find($dispatchSheet->id);

                if (! $sheet || $sheet->status !== 'pending') {
                    throw new \RuntimeException('Only pending dispatch sheets can be cancelled — this one has already been processed.');
                }

                $sheet->load('items.sku');
                $this->stockService->releaseStock($sheet);

                $sheet->update([
                    'status' => 'cancelled',
                    'cancel_reason' => $request->cancel_reason,
                    'cancelled_at' => now(),
                ]);
            });

            return redirect()->route('dispatch-sheets.show', $dispatchSheet)
                ->with('success', 'Dispatch sheet cancelled. Stock has been released.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', 'Failed to cancel the dispatch sheet. Please try again; if it keeps happening, contact your administrator.');
        }
    }

    /**
     * Generated fresh on every download and streamed straight back — never
     * written to disk. A copy on the public disk was reachable at a
     * predictable /storage URL with no login, and went stale the moment a
     * sheet was confirmed or cancelled.
     */
    public function downloadPdf(DispatchSheet $dispatchSheet)
    {
        return $this->pdfService->generateDispatchPdf($dispatchSheet)
            ->download("{$dispatchSheet->ds_number}.pdf");
    }

    /**
     * A GST-compliant Road/Delivery Challan for this dispatch — a separate
     * document from the plain Dispatch Sheet PDF above.
     */
    public function downloadChallan(DispatchSheet $dispatchSheet)
    {
        if ($issue = $this->pdfService->challanBlocker($dispatchSheet)) {
            return back()->with('error', $issue);
        }

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

    /**
     * "GIP-001 x 40 @ 480.00 [HSN 7306], ..." — a readable Activity Log
     * summary. Rate and HSN are included so an edit that changes only the
     * price on a line (what the challan totals come from) still produces a
     * differing snapshot and gets logged.
     */
    private function itemsSummary($items): string
    {
        return collect($items)->map(function ($item) {
            $qty = rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.');
            $rate = $item->unit_price === null ? '-' : number_format((float) $item->unit_price, 2, '.', '');
            $hsn = $item->hsn_code ?: '-';
            return "{$item->sku->code} x {$qty} @ {$rate} [HSN {$hsn}]";
        })->implode(', ');
    }

    /** Carbon casts (delivery_date) need to be plain strings to compare/log cleanly. */
    private function normalizeForLog($value)
    {
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
    }
}
