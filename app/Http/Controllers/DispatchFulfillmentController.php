<?php

namespace App\Http\Controllers;

use App\Models\DispatchSheet;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DispatchFulfillmentController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    // The pending queue moved to the "To send" tab on the Dispatch screen; the
    // /fulfillment URL redirects there. Only the detail view and the confirm
    // action still live here.

    public function show(DispatchSheet $dispatchSheet)
    {
        $dispatchSheet->load(['items.sku', 'godown', 'creator']);
        return view('dispatch-fulfillment.show', compact('dispatchSheet'));
    }

    public function confirm(DispatchSheet $dispatchSheet)
    {
        try {
            DB::transaction(function () use ($dispatchSheet) {
                // Reload with lock to prevent race conditions
                $sheet = DispatchSheet::lockForUpdate()->find($dispatchSheet->id);

                if ($sheet->status !== 'pending') {
                    throw new \RuntimeException('This dispatch sheet has already been processed.');
                }

                $sheet->load('items.sku');
                $this->stockService->processDispatch($sheet);

                $sheet->update([
                    'status' => 'dispatched',
                    'dispatched_at' => now(),
                    'dispatched_by' => auth()->id(),
                ]);
            });

            // Back to the queue, which now lives as a tab on the Dispatch screen.
            return redirect()->route('dispatch-sheets.index', ['tab' => 'to-send'])
                ->with('success', "{$dispatchSheet->ds_number} sent out.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to confirm dispatch: ' . $e->getMessage());
        }
    }
}
