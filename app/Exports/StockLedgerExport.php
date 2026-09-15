<?php

namespace App\Exports;

use App\Models\StockLedger;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockLedgerExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private array $filters)
    {
    }

    public function collection()
    {
        $query = StockLedger::with(['sku', 'godown', 'performer'])
            ->whereDate('created_at', '>=', $this->filters['date_from'] ?? today()->subMonth())
            ->whereDate('created_at', '<=', $this->filters['date_to'] ?? today());

        if (!empty($this->filters['sku_id'])) {
            $query->where('sku_id', $this->filters['sku_id']);
        }

        if (!empty($this->filters['godown_id'])) {
            $query->where('godown_id', $this->filters['godown_id']);
        }

        if (!empty($this->filters['movement_type'])) {
            $query->where('movement_type', $this->filters['movement_type']);
        }

        return $query->latest('created_at')->get();
    }

    public function headings(): array
    {
        return ['Date/Time', 'Type', 'SKU Code', 'Product', 'Quantity', 'Balance After', 'Godown', 'Reference', 'Performed By'];
    }

    public function map($entry): array
    {
        return [
            $entry->created_at->format('d/m/Y H:i'),
            str_replace('_', ' ', ucfirst($entry->movement_type)),
            $entry->sku->code ?? '-',
            $entry->sku->name ?? '-',
            $entry->quantity > 0 ? '+' . $entry->quantity : $entry->quantity,
            $entry->balance_after,
            $entry->godown->name ?? '-',
            ucfirst($entry->reference_type) . ' #' . $entry->reference_id,
            $entry->performer->name ?? '-',
        ];
    }
}
