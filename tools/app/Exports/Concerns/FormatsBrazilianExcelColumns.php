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
            $header = (string) $header;

            if (BrazilianNumberFormatter::isCurrencyHeader($header)) {
                $formats[$columnLetter] = BrazilianNumberFormatter::EXCEL_CURRENCY;
            } elseif (BrazilianNumberFormatter::isNumberHeader($header)) {
                $formats[$columnLetter] = BrazilianNumberFormatter::EXCEL_INTEGER;
            } elseif (BrazilianNumberFormatter::isCodeHeader($header)) {
                $formats[$columnLetter] = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT;
            }
        }

        return $formats;
    }
}
