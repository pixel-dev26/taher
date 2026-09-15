<?php

namespace App\Support;

class Money
{
    /**
     * Rupees with Indian digit grouping: ₹12,34,567.50.
     *
     * number_format() only groups in thousands, and the intl extension that
     * would do this is not guaranteed on the server. Pass $decimals = 0 for a
     * compact whole-rupee figure, e.g. a dashboard tile.
     */
    public static function inr(?float $amount, string $empty = '—', int $decimals = 2): string
    {
        if ($amount === null) {
            return $empty;
        }

        $formatted = number_format(abs($amount), $decimals, '.', '');
        [$whole, $fraction] = str_contains($formatted, '.') ? explode('.', $formatted) : [$formatted, null];

        // Last three digits stay together; everything before them goes in pairs.
        $grouped = substr($whole, -3);
        $rest = substr($whole, 0, -3);

        if ($rest !== '') {
            $grouped = preg_replace('/\B(?=(\d{2})+$)/', ',', $rest) . ',' . $grouped;
        }

        return ($amount < 0 ? '-' : '') . '₹' . $grouped . ($fraction !== null ? '.' . $fraction : '');
    }
}
