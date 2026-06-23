<?php

namespace App\Support;

class BrazilianNumberFormatter
{
    public const EXCEL_INTEGER = '#.##0';

    public const EXCEL_DECIMAL = '#.##0,00';

    public const EXCEL_CURRENCY = '[$R$-416] #.##0,00;[Red]-[$R$-416] #.##0,00';

    public static function formatInteger(float|int|string|null $value): string
    {
        return number_format((float) ($value ?? 0), 0, ',', '.');
    }

    public static function formatCurrency(float|int|string|null $value): string
    {
        return 'R$ ' . number_format((float) ($value ?? 0), 2, ',', '.');
    }

    /**
     * Converte valor já formatado para exibição (pt-BR) em número puro para o Excel.
     */
    public static function parseForExcel(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        if (str_starts_with($value, 'R$')) {
            $numericValue = preg_replace('/[^\d,.-]/', '', $value);
            $numericValue = str_replace(['.', ','], ['', '.'], $numericValue);

            return (float) $numericValue;
        }

        // Strings começando com '0' são códigos (CNES, procedimento, grupo, etc.)
        // Nunca converter — preservar zeros à esquerda.
        if (str_starts_with($value, '0')) {
            return $value;
        }

        if (preg_match('/^\d{1,3}(\.\d{3})*$/', $value)) {
            return (int) str_replace('.', '', $value);
        }

        if (preg_match('/^\d{1,3}(\.\d{3})*,\d+$/', $value)) {
            $numericValue = str_replace(['.', ','], ['', '.'], $value);

            return (float) $numericValue;
        }

        return $value;
    }

    public static function isCurrencyHeader(string $header): bool
    {
        $header = mb_strtolower($header);

        foreach ([
            'valor',
            'cismetro - valor',
            'unitário',
            'unitario',
            'cismetro - valor unitário',
            'cismetro - valor unitario',
            'cismetro - valor total',
        ] as $keyword) {
            if (str_contains($header, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public static function isNumberHeader(string $header): bool
    {
        $header = mb_strtolower($header);

        if (self::isCurrencyHeader($header)) {
            return false;
        }

        foreach ([
            'quantidade',
            'qtd',
            'número',
            'numero',
        ] as $keyword) {
            if (str_contains($header, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Colunas cujo conteúdo é código alfanumérico (CNES, procedimento, grupo etc.).
     * Devem ser formatadas como texto (@) no Excel para preservar zeros à esquerda.
     */
    public static function isCodeHeader(string $header): bool
    {
        $lower = mb_strtolower(trim($header));

        foreach ([
            'cnes',
            'grupo',
            'subgrupo',
            'forma de',
            'proc.',
            'procedimento',
            'especialidade',
            'complexidade',
            'motivo',
            'financiamento',
            'diag',
            'diagnós',
            'aih',
            'enfermaria',
            'sexo',
            'cbo',
        ] as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public static function isCurrencyField(string $field): bool
    {
        return in_array($field, [
            'PRD_VL_P',
            'PAP_VALOR',
            'BPI_VL_P',
            'cismetro_total',
            'cismetro_valor',
        ], true);
    }

    public static function excelFormatForField(string $field): ?string
    {
        if (self::isCurrencyField($field)) {
            return self::EXCEL_DECIMAL;
        }

        if (in_array($field, ['PRD_QT_P', 'PAP_QT_P', 'BPI_QT_P'], true)) {
            return self::EXCEL_INTEGER;
        }

        return null;
    }

    /**
     * Valor para célula da matriz no Excel: número puro (1 campo) ou texto pt-BR (vários campos).
     */
    public static function formatMatrixExportValues(array $numericFields, array $values): mixed
    {
        if (count($numericFields) === 1) {
            $field = $numericFields[0];

            return (float) ($values[$field] ?? 0);
        }

        $parts = [];
        foreach ($numericFields as $field) {
            $value = $values[$field] ?? 0;
            $parts[] = self::isCurrencyField($field)
                ? self::formatCurrency($value)
                : self::formatInteger($value);
        }

        return implode(' / ', $parts) ?: '0';
    }
}
