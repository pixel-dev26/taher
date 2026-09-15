<?php

namespace App\Exports;

use App\Services\StockService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StockAsOnDateExport implements FromArray, WithHeadings
{
    public function __construct(private array $filters)
    {
    }

    public function array(): array
    {
        $date = $this->filters['date'] ?? today()->format('Y-m-d');
        $godownId = $this->filters['godown_id'] ?? null;

        // Same figures as the on-screen report — see StockService::stockAsOnDate,
        // which replaced the per SKU/godown query pair this used to run.
        $result = app(StockService::class)->stockAsOnDate($date, $godownId ? (int) $godownId : null);
        $godowns = $result['godowns']->keyBy('id');

        $rows = [];

        foreach ($result['rows'] as $entry) {
            $sku = $entry['sku'];

            foreach ($entry['godowns'] as $id => $figures) {
                if ($figures['on_hand'] > 0 || $figures['reserved'] > 0) {
                    $rows[] = [
                        $sku->code,
                        $sku->name,
                        $sku->category,
                        $sku->unit_of_measure,
                        $godowns[$id]->name,
                        $figures['on_hand'],
                        $figures['reserved'],
                        $figures['available'],
                    ];
                }
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['SKU Code', 'Product', 'Category', 'UoM', 'Godown', 'On-Hand', 'Reserved', 'Available'];
    }
}
