<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreDispatchSheetRequest;
use App\Http\Requests\UpdateDispatchSheetRequest;
use App\Models\DispatchSheet;
use App\Models\DispatchSheetItem;
use App\Models\Godown;
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

                $sheet = DispatchSheet::create([
                    'ds_number' => $dsNumber,
                    'godown_id' => $request->godown_id,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                    'customer_name' => $request->customer_name,
                    'delivery_address' => $request->delivery_address,
                    'delivery_date' => $request->delivery_date,
                    'vehicle_no' => $request->vehicle_no,
                    'driver_name' => $request->driver_name,
                    'driver_phone' => $request->driver_phone,
                    'notes' => $request->notes,
                ]);

                foreach ($request->items as $item) {
                    DispatchSheetItem::create([
                        'dispatch_sheet_id' => $sheet->id,
                        'sku_id' => $item['sku_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }

                $sheet->load(['items.sku', 'godown']);
                $this->stockService->reserveStock($sheet);

                // Generate PDF
                $this->pdfService->generateDispatchPdf($sheet);

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
                $oldItems = $dispatchSheet->items->pluck('quantity', 'sku_id')
                    ->map(fn($q) => (float)$q)->toArray();

                $dispatchSheet->update([
                    'customer_name' => $request->customer_name,
                    'delivery_address' => $request->delivery_address,
                    'delivery_date' => $request->delivery_date,
                    'vehicle_no' => $request->vehicle_no,
                    'driver_name' => $request->driver_name,
                    'driver_phone' => $request->driver_phone,
                    'notes' => $request->notes,
                ]);

                // Delete old items and create new ones
                $dispatchSheet->items()->delete();
                foreach ($request->items as $item) {
                    DispatchSheetItem::create([
                        'dispatch_sheet_id' => $dispatchSheet->id,
                        'sku_id' => $item['sku_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }

                $newItems = collect($request->items)->pluck('quantity', 'sku_id')
                    ->map(fn($q) => (float)$q)->toArray();

                $dispatchSheet->load(['items.sku', 'godown']);
                $this->stockService->updateReservation($dispatchSheet, $oldItems, $newItems);

                // Regenerate PDF
                $this->pdfService->generateDispatchPdf($dispatchSheet);
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
        if (!$dispatchSheet->pdf_path) {
            // Generate on the fly
            $this->pdfService->generateDispatchPdf($dispatchSheet);
            $dispatchSheet->refresh();
        }

        if (!Storage::disk('public')->exists($dispatchSheet->pdf_path)) {
            abort(404, 'PDF not found.');
        }

        return Storage::disk('public')->download($dispatchSheet->pdf_path, "{$dispatchSheet->ds_number}.pdf");
    }
}
