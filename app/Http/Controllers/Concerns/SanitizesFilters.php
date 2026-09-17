<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * List and report screens take their filters from the query string, where a
 * stale bookmark or a tampered link can put anything (an array where a date
 * was expected, a status that doesn't exist). Rather than 500 on it, drop
 * whatever fails the rules — from the request itself, so the Blade filter
 * form (which reads request()) and withQueryString() see clean values too.
 */
trait SanitizesFilters
{
    /** @return array<string, mixed> only the filters that passed their rules */
    protected function filters(Request $request, array $rules): array
    {
        $valid = validator($request->query(), $rules)->valid();

        // Keys without a rule (e.g. page) are left alone; ruled keys survive
        // only if they validated.
        $request->query->replace(array_diff_key($request->query(), $rules) + $valid);

        return $valid;
    }
}
