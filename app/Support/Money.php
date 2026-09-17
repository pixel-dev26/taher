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

    /**
     * An amount spelled out for a printed document, e.g. "ONE THOUSAND ONE
     * HUNDRED AND EIGHTY-NINE RUPEES ONLY" — the "Total in words" line on an
     * invoice or delivery challan. Uses the Indian numbering system (lakh /
     * crore), since that is what these documents are read against.
     *
     * Only sized for realistic invoice amounts (comfortably under a crore);
     * larger figures still produce a value, just not one worth relying on.
     */
    public static function words(float $amount): string
    {
        // Settle to paise first, the same rounding number_format() applies
        // when the figure is printed — otherwise 12.995 prints as 13.00 but
        // would be spelled "TWELVE RUPEES AND ONE HUNDRED PAISE".
        $paiseTotal = (int) round(abs($amount) * 100);
        $rupees = intdiv($paiseTotal, 100);
        $paise = $paiseTotal % 100;

        $words = self::numberToIndianWords($rupees) . ' RUPEES';

        if ($paise > 0) {
            $words .= ' AND ' . self::numberToIndianWords($paise) . ' PAISE';
        }

        return ($amount < 0 ? 'MINUS ' : '') . $words . ' ONLY';
    }

    private static function numberToIndianWords(int $number): string
    {
        if ($number === 0) {
            return 'ZERO';
        }

        $ones = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE',
            'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN',
            'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'];
        $tens = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];

        // 0-99, e.g. 89 -> "EIGHTY-NINE".
        $twoDigits = function (int $n) use ($ones, $tens): string {
            if ($n === 0) return '';
            if ($n < 20) return $ones[$n];
            $ten = intdiv($n, 10);
            $one = $n % 10;
            return $tens[$ten] . ($one ? '-' . $ones[$one] : '');
        };

        // 0-999, e.g. 189 -> "ONE HUNDRED AND EIGHTY-NINE".
        $threeDigits = function (int $n) use ($ones, $twoDigits): string {
            $hundred = intdiv($n, 100);
            $rest = $n % 100;
            $parts = [];
            if ($hundred) $parts[] = $ones[$hundred] . ' HUNDRED';
            if ($rest) $parts[] = $twoDigits($rest);
            return implode(' AND ', $parts);
        };

        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        $lakh = intdiv($number, 100000);
        $number %= 100000;
        $thousand = intdiv($number, 1000);
        $hundred = $number % 1000;

        $segments = [];
        // Crores recurse (100+ crore reads "ONE HUNDRED CRORE"), since the
        // 0-99 helper would otherwise run off the end of its tables.
        if ($crore) $segments[] = self::numberToIndianWords($crore) . ' CRORE';
        if ($lakh) $segments[] = $twoDigits($lakh) . ' LAKH';
        if ($thousand) $segments[] = $twoDigits($thousand) . ' THOUSAND';
        if ($hundred) $segments[] = $threeDigits($hundred);

        return implode(' ', $segments);
    }
}
