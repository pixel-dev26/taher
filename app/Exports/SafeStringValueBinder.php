<?php

namespace App\Exports;

use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * The stock binder turns any string beginning with "=" into a live formula,
 * so a product or customer named =HYPERLINK(...) would execute when the
 * owner opens an export. Anything a person typed is written as text;
 * numbers, dates and booleans still bind normally. Wired in via
 * config/excel.php so every export gets it without opting in.
 */
class SafeStringValueBinder extends DefaultValueBinder
{
    private const FORMULA_STARTERS = ['=', '+', '-', '@', "\t", "\r"];

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && $value !== '' && in_array($value[0], self::FORMULA_STARTERS, true) && ! is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
