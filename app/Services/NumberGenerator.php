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

            // Compare the sequence numerically, not as text: sorted as
            // strings, "...-999" outranks "...-1000", so the day's 1001st
            // document would collide with the 1000th forever after.
            $lastSeq = DB::table($table)
                ->where($column, 'LIKE', $pattern)
                ->lockForUpdate()
                ->pluck($column)
                ->map(fn ($number) => (int) substr($number, strrpos($number, '-') + 1))
                ->max() ?? 0;

            return sprintf('%s-%s-%03d', $prefix, $today, $lastSeq + 1);
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
