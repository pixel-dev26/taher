<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NumberGenerator
{
    public static function generate(string $prefix, string $table, string $column): string
    {
        return DB::transaction(function () use ($prefix, $table, $column) {
            $today = now()->format('Ymd');
            $pattern = "{$prefix}-{$today}-%";

            $latest = DB::table($table)
                ->where($column, 'LIKE', $pattern)
                ->lockForUpdate()
                ->orderByDesc($column)
                ->value($column);

            if ($latest) {
                $lastSeq = (int)substr($latest, -3);
                $newSeq = $lastSeq + 1;
            } else {
                $newSeq = 1;
            }

            return sprintf('%s-%s-%03d', $prefix, $today, $newSeq);
        });
    }

    public static function grn(): string
    {
        return self::generate('GRN', 'grns', 'grn_number');
    }

    public static function dispatchSheet(): string
    {
        return self::generate('DS', 'dispatch_sheets', 'ds_number');
    }

    public static function transfer(): string
    {
        return self::generate('TRF', 'stock_transfers', 'transfer_number');
    }

    public static function adjustment(): string
    {
        return self::generate('ADJ', 'stock_adjustments', 'adjustment_number');
    }
}
