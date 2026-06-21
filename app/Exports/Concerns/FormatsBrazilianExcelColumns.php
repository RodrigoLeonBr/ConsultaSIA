<?php

namespace App\Exports\Concerns;

use App\Support\BrazilianNumberFormatter;

trait FormatsBrazilianExcelColumns
{
    protected function processValueForExcel(mixed $value): mixed
    {
        return BrazilianNumberFormatter::parseForExcel($value);
    }

    protected function columnFormatsForHeaders(array $headers): array
    {
        $formats = [];

        foreach ($headers as $index => $header) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);

            if (BrazilianNumberFormatter::isCurrencyHeader((string) $header)) {
                $formats[$columnLetter] = BrazilianNumberFormatter::EXCEL_CURRENCY;
            } elseif (BrazilianNumberFormatter::isNumberHeader((string) $header)) {
                $formats[$columnLetter] = BrazilianNumberFormatter::EXCEL_INTEGER;
            }
        }

        return $formats;
    }
}
