<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\{StockRecord, StockLedger, Grn, DispatchSheet, StockTransfer, StockAdjustment, Godown, Sku};
use Illuminate\Support\Facades\DB;

class StockService
{
    public function getStockRecordForUpdate(int $skuId, int $godownId): StockRecord
    {
        return StockRecord::where('sku_id', $skuId)
            ->where('godown_id', $godownId)
            ->lockForUpdate()
            ->firstOrCreate(
                ['sku_id' => $skuId, 'godown_id' => $godownId],
                ['on_hand' => 0, 'reserved' => 0]
            );
    }

    /**
     * Stock position for every SKU as at the end of the given date.
     *
     * Shared by the stock-as-on-date report and its Excel export, which each
     * previously walked SKUs x godowns issuing two queries per pair — roughly
     * 900 queries per run for 152 SKUs across 3 godowns. This runs in four,
     * regardless of catalogue size.
     *
     * @return array{godowns: \Illuminate\Support\Collection, rows: array}
     */
    /**
     * On-hand / reserved / available per (sku, godown) at the end of a date.
     *
     * Returns [skuId][godownId] => ['on_hand', 'reserved', 'available'].
     * Pass $skuIds to limit the work to one page of results.
     */
    public function stockFigures(string $date, ?array $skuIds = null): array
    {
        $figures = [];

        // For today the live balance table is the source of truth, and it is
        // the only place reservations exist.
        if ($date === today()->format('Y-m-d')) {
            $query = StockRecord::query();

            if ($skuIds !== null) {
                $query->whereIn('sku_id', $skuIds);
            }

            foreach ($query->get() as $record) {
                $onHand = (float) $record->on_hand;
                $reserved = (float) $record->reserved;

                $figures[$record->sku_id][$record->godown_id] = [
                    'on_hand' => $onHand,
                    'reserved' => $reserved,
                    'available' => $onHand - $reserved,
                ];
            }

            return $figures;
        }

        // For a past date, reconstruct the closing balance from the ledger. It
        // is append-only — StockLedger::delete() throws — so the highest id per
        // (sku, godown) is the latest entry, which lets one grouped query
        // replace a per-pair "order by created_at desc limit 1" lookup.
        $bindings = [$date . ' 23:59:59'];
        $skuFilter = '';

        if ($skuIds !== null && $skuIds !== []) {
            $skuFilter = ' AND sku_id IN (' . implode(',', array_fill(0, count($skuIds), '?')) . ')';
            $bindings = array_merge($bindings, $skuIds);
        }

        $rows = DB::select(
            "SELECT sl.sku_id, sl.godown_id, sl.balance_after
               FROM stock_ledger sl
               JOIN (
                    SELECT sku_id, godown_id, MAX(id) AS max_id
                      FROM stock_ledger
                     WHERE created_at <= ?{$skuFilter}
                  GROUP BY sku_id, godown_id
               ) m ON sl.id = m.max_id",
            $bindings
        );

        foreach ($rows as $row) {
            $onHand = (float) $row->balance_after;

            // Reservations are a live figure; they mean nothing historically.
            $figures[$row->sku_id][$row->godown_id] = [
                'on_hand' => $onHand,
                'reserved' => 0.0,
                'available' => $onHand,
            ];
        }

        return $figures;
    }

    /**
     * Whole-catalogue stock position, shaped for the report and its export.
     *
     * @return array{godowns: \Illuminate\Support\Collection, rows: array}
     */
    public function stockAsOnDate(string $date, ?int $godownId = null): array
    {
        $godowns = $godownId
            ? Godown::where('id', $godownId)->get()
            : Godown::active()->get();

        $skus = Sku::active()->orderBy('code')->get();
        $figures = $this->stockFigures($date);

        $rows = [];
        $empty = ['on_hand' => 0, 'reserved' => 0, 'available' => 0];

        foreach ($skus as $sku) {
            $entry = [
                'sku' => $sku,
                'godowns' => [],
                'total_on_hand' => 0,
                'total_reserved' => 0,
                'total_available' => 0,
            ];

            foreach ($godowns as $godown) {
                $f = $figures[$sku->id][$godown->id] ?? $empty;

                $entry['godowns'][$godown->id] = $f;
                $entry['total_on_hand'] += $f['on_hand'];
                $entry['total_reserved'] += $f['reserved'];
                $entry['total_available'] += $f['available'];
            }

            if ($entry['total_on_hand'] > 0 || $entry['total_reserved'] > 0) {
                $rows[] = $entry;
            }
        }

        return ['godowns' => $godowns, 'rows' => $rows];
    }

    /**
     * Weighted average purchase price per SKU, from every priced receipt.
     *
     * Each batch counts in proportion to its quantity: 10 @ 100 then 30 @ 120
     * averages 115, not 110. Receipts recorded before prices were captured are
     * skipped. With a date, only receipts on or before it count, so the Stock
     * screen's "as at" view shows the price as it stood then.
     *
     * Returns [skuId => ['average' => float, 'quantity' => float, 'receipts' => int]].
     */
    public function averagePrices(array $skuIds, ?string $date = null): array
    {
        if ($skuIds === []) {
            return [];
        }

        $rows = DB::table('grn_items')
            ->join('grns', 'grns.id', '=', 'grn_items.grn_id')
            ->whereIn('grn_items.sku_id', $skuIds)
            ->whereNotNull('grn_items.unit_price')
            ->when($date, fn ($q) => $q->where('grns.receipt_date', '<=', $date . ' 23:59:59'))
            ->groupBy('grn_items.sku_id')
            ->selectRaw('grn_items.sku_id,
                SUM(grn_items.quantity * grn_items.unit_price) AS total_amount,
                SUM(grn_items.quantity) AS total_quantity,
                COUNT(DISTINCT grn_items.grn_id) AS receipts')
            ->get();

        $prices = [];

        foreach ($rows as $row) {
            $quantity = (float) $row->total_quantity;

            if ($quantity <= 0) {
                continue;
            }

            $prices[$row->sku_id] = [
                'average' => (float) $row->total_amount / $quantity,
                'quantity' => $quantity,
                'receipts' => (int) $row->receipts,
            ];
        }

        return $prices;
    }

    public function getAvailable(int $skuId, int $godownId): float
    {
        $record = StockRecord::where('sku_id', $skuId)
            ->where('godown_id', $godownId)
            ->first();

        if (!$record) {
            return 0;
        }

        return (float)$record->on_hand - (float)$record->reserved;
    }

    public function processStockIn(Grn $grn): void
    {
        DB::transaction(function () use ($grn) {
            foreach ($grn->items as $item) {
                $record = $this->getStockRecordForUpdate($item->sku_id, $grn->godown_id);
                $record->on_hand = (float)$record->on_hand + (float)$item->quantity;
                $record->save();

                $this->createLedgerEntry(
                    $item->sku_id,
                    $grn->godown_id,
                    'stock_in',
                    (float)$item->quantity,
                    (float)$record->on_hand,
                    'grn',
                    $grn->id,
                    $grn->created_by,
                    "GRN: {$grn->grn_number}"
                );
            }
        });
    }

    public function reserveStock(DispatchSheet $sheet): void
    {
        DB::transaction(function () use ($sheet) {
            foreach ($sheet->items as $item) {
                $record = $this->getStockRecordForUpdate($item->sku_id, $sheet->godown_id);
                $available = (float)$record->on_hand - (float)$record->reserved;

                if ($available < (float)$item->quantity) {
                    throw new InsufficientStockException(
                        $item->sku->code,
                        $sheet->godown->name,
                        $available,
                        (float)$item->quantity
                    );
                }

                $record->reserved = (float)$record->reserved + (float)$item->quantity;
                $record->save();

                $this->createLedgerEntry(
                    $item->sku_id,
                    $sheet->godown_id,
                    'reserved',
                    (float)$item->quantity,
                    (float)$record->on_hand,
                    'dispatch_sheet',
                    $sheet->id,
                    $sheet->created_by,
                    "DS: {$sheet->ds_number}"
                );
            }
        });
    }

    public function releaseStock(DispatchSheet $sheet): void
    {
        DB::transaction(function () use ($sheet) {
            foreach ($sheet->items as $item) {
                $record = $this->getStockRecordForUpdate($item->sku_id, $sheet->godown_id);
                $record->reserved = max(0, (float)$record->reserved - (float)$item->quantity);
                $record->save();

                $this->createLedgerEntry(
                    $item->sku_id,
                    $sheet->godown_id,
                    'reserve_released',
                    -(float)$item->quantity,
                    (float)$record->on_hand,
                    'dispatch_sheet',
                    $sheet->id,
                    auth()->id(),
                    "DS Cancelled: {$sheet->ds_number}"
                );
            }
        });
    }

    public function updateReservation(DispatchSheet $sheet, array $oldItems, array $newItems): void
    {
        DB::transaction(function () use ($sheet, $oldItems, $newItems) {
            $allSkuIds = array_unique(array_merge(array_keys($oldItems), array_keys($newItems)));

            foreach ($allSkuIds as $skuId) {
                $oldQty = $oldItems[$skuId] ?? 0;
                $newQty = $newItems[$skuId] ?? 0;
                $delta = $newQty - $oldQty;

                if ($delta == 0) continue;

                $record = $this->getStockRecordForUpdate($skuId, $sheet->godown_id);

                if ($delta > 0) {
                    // Need more stock
                    $available = (float)$record->on_hand - (float)$record->reserved;
                    if ($available < $delta) {
                        $sku = \App\Models\Sku::find($skuId);
                        throw new InsufficientStockException(
                            $sku->code,
                            $sheet->godown->name,
                            $available,
                            $delta
                        );
                    }
                    $record->reserved = (float)$record->reserved + $delta;
                    $movementType = 'reserved';
                } else {
                    // Release stock
                    $record->reserved = max(0, (float)$record->reserved + $delta); // delta is negative
                    $movementType = 'reserve_released';
                }

                $record->save();

                $this->createLedgerEntry(
                    $skuId,
                    $sheet->godown_id,
                    $movementType,
                    $delta,
                    (float)$record->on_hand,
                    'dispatch_sheet',
                    $sheet->id,
                    auth()->id(),
                    "DS Updated: {$sheet->ds_number}"
                );
            }
        });
    }

    public function processDispatch(DispatchSheet $sheet): void
    {
        DB::transaction(function () use ($sheet) {
            foreach ($sheet->items as $item) {
                $record = $this->getStockRecordForUpdate($item->sku_id, $sheet->godown_id);
                $record->on_hand = (float)$record->on_hand - (float)$item->quantity;
                $record->reserved = (float)$record->reserved - (float)$item->quantity;
                $record->save();

                $this->createLedgerEntry(
                    $item->sku_id,
                    $sheet->godown_id,
                    'dispatch_out',
                    -(float)$item->quantity,
                    (float)$record->on_hand,
                    'dispatch_sheet',
                    $sheet->id,
                    auth()->id(),
                    "DS Dispatched: {$sheet->ds_number}"
                );
            }
        });
    }

    public function processTransferOut(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $record = $this->getStockRecordForUpdate($item->sku_id, $transfer->source_godown_id);
                $available = (float)$record->on_hand - (float)$record->reserved;

                if ($available < (float)$item->quantity) {
                    throw new InsufficientStockException(
                        $item->sku->code,
                        $transfer->sourceGodown->name,
                        $available,
                        (float)$item->quantity
                    );
                }

                $record->on_hand = (float)$record->on_hand - (float)$item->quantity;
                $record->save();

                $this->createLedgerEntry(
                    $item->sku_id,
                    $transfer->source_godown_id,
                    'transfer_out',
                    -(float)$item->quantity,
                    (float)$record->on_hand,
                    'stock_transfer',
                    $transfer->id,
                    $transfer->created_by,
                    "TRF Out: {$transfer->transfer_number}"
                );
            }
        });
    }

    public function processTransferAccept(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $record = $this->getStockRecordForUpdate($item->sku_id, $transfer->dest_godown_id);
                $record->on_hand = (float)$record->on_hand + (float)$item->quantity;
                $record->save();

                $this->createLedgerEntry(
                    $item->sku_id,
                    $transfer->dest_godown_id,
                    'transfer_in',
                    (float)$item->quantity,
                    (float)$record->on_hand,
                    'stock_transfer',
                    $transfer->id,
                    auth()->id(),
                    "TRF In: {$transfer->transfer_number}"
                );
            }
        });
    }

    public function processTransferReject(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            foreach ($transfer->items as $item) {
                $record = $this->getStockRecordForUpdate($item->sku_id, $transfer->source_godown_id);
                $record->on_hand = (float)$record->on_hand + (float)$item->quantity;
                $record->save();

                $this->createLedgerEntry(
                    $item->sku_id,
                    $transfer->source_godown_id,
                    'transfer_rejected',
                    (float)$item->quantity,
                    (float)$record->on_hand,
                    'stock_transfer',
                    $transfer->id,
                    auth()->id(),
                    "TRF Rejected: {$transfer->transfer_number}"
                );
            }
        });
    }

    public function processAdjustment(StockAdjustment $adjustment): void
    {
        DB::transaction(function () use ($adjustment) {
            foreach ($adjustment->items as $item) {
                $record = $this->getStockRecordForUpdate($item->sku_id, $adjustment->godown_id);

                if ((float)$item->quantity < 0) {
                    $availableForAdjust = (float)$record->on_hand - (float)$record->reserved;
                    if ($availableForAdjust < abs((float)$item->quantity)) {
                        throw new InsufficientStockException(
                            $item->sku->code,
                            $adjustment->godown->name,
                            $availableForAdjust,
                            abs((float)$item->quantity)
                        );
                    }
                }

                $record->on_hand = (float)$record->on_hand + (float)$item->quantity;
                $record->save();

                $this->createLedgerEntry(
                    $item->sku_id,
                    $adjustment->godown_id,
                    'adjustment',
                    (float)$item->quantity,
                    (float)$record->on_hand,
                    'stock_adjustment',
                    $adjustment->id,
                    $adjustment->created_by,
                    "ADJ: {$adjustment->adjustment_number} ({$adjustment->reason})"
                );
            }
        });
    }

    private function createLedgerEntry(
        int $skuId,
        int $godownId,
        string $movementType,
        float $quantity,
        float $balanceAfter,
        string $referenceType,
        int $referenceId,
        int $performedBy,
        ?string $notes = null
    ): StockLedger {
        return StockLedger::create([
            'sku_id' => $skuId,
            'godown_id' => $godownId,
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'performed_by' => $performedBy,
            'notes' => $notes,
        ]);
    }
}
