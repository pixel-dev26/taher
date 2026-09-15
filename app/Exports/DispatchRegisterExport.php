<?php

namespace App\Exports;

use App\Models\DispatchSheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DispatchRegisterExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private array $filters)
    {
    }

    public function collection()
    {
        $query = DispatchSheet::with(['items.sku', 'godown', 'creator'])
            ->whereDate('created_at', '>=', $this->filters['date_from'] ?? today()->subMonth())
            ->whereDate('created_at', '<=', $this->filters['date_to'] ?? today());

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['godown_id'])) {
            $query->where('godown_id', $this->filters['godown_id']);
        }

        // Flatten to one row per line item
        $rows = collect();
        foreach ($query->latest()->get() as $sheet) {
            foreach ($sheet->items as $item) {
                $rows->push((object)[
                    'ds_number' => $sheet->ds_number,
                    'godown' => $sheet->godown->name,
                    'created_by' => $sheet->creator->name,
                    'created_at' => $sheet->created_at->format('d/m/Y'),
                    'delivery_date' => $sheet->delivery_date?->format('d/m/Y') ?? '-',
                    'customer' => $sheet->customer_name ?? '-',
                    'status' => ucfirst($sheet->status),
                    'sku_code' => $item->sku->code,
                    'sku_name' => $item->sku->name,
                    'quantity' => $item->quantity,
                    'uom' => $item->sku->unit_of_measure,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['DS Number', 'Godown', 'Created By', 'Created Date', 'Delivery Date', 'Customer', 'Status', 'SKU Code', 'Product', 'Quantity', 'UoM'];
    }

    public function map($row): array
    {
        return [
            $row->ds_number, $row->godown, $row->created_by, $row->created_at,
            $row->delivery_date, $row->customer, $row->status,
            $row->sku_code, $row->sku_name, $row->quantity, $row->uom,
        ];
    }
}
