<?php

namespace App\Support;

class Money
{
    /**
     * Rupees with Indian digit grouping: ₹12,34,567.50.
     *
     * number_format() only groups in thousands, and the intl extension that
     * would do this is not guaranteed on the server.
     */
    public static function inr(?float $amount, string $empty = '—'): string
    {
        if ($amount === null) {
            return $empty;
        }

        [$whole, $paise] = explode('.', number_format(abs($amount), 2, '.', ''));

        // Last three digits stay together; everything before them goes in pairs.
        $grouped = substr($whole, -3);
        $rest = substr($whole, 0, -3);

        if ($rest !== '') {
            $grouped = preg_replace('/\B(?=(\d{2})+$)/', ',', $rest) . ',' . $grouped;
        }

        return ($amount < 0 ? '-' : '') . '₹' . $grouped . '.' . $paise;
    }
}
