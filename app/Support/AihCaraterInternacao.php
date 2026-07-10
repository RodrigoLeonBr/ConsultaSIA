<?php

namespace App\Support;

class AihCaraterInternacao
{
    /**
     * @var array<string, array{sihd: string, resumo: string}>
     */
    private const MAP = [
        '01' => ['sihd' => 'Eletivo', 'resumo' => 'Eletivo'],
        '02' => ['sihd' => 'Urgência', 'resumo' => 'Urgência / Emergência'],
        '03' => ['sihd' => 'Acidente no trajeto para o trabalho', 'resumo' => 'Urgência (Acidente)'],
        '04' => ['sihd' => 'Outros tipos de acidentes de trabalho', 'resumo' => 'Urgência (Acidente)'],
        '05' => ['sihd' => 'Outros tipos de acidentes', 'resumo' => 'Urgência (Acidente)'],
        '06' => ['sihd' => 'Pós-parto normal / cesárea', 'resumo' => 'Obstetrícia'],
    ];

    public static function label(?string $code): string
    {
        $code = self::normalizeCode($code);

        if ($code === null) {
            return '';
        }

        return self::MAP[$code]['sihd'] ?? "Código {$code}";
    }

    public static function resumo(?string $code): string
    {
        $code = self::normalizeCode($code);

        if ($code === null) {
            return '';
        }

        return self::MAP[$code]['resumo'] ?? "Código {$code}";
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function lookupOptions(?string $search = null): array
    {
        $options = collect(self::MAP)
            ->map(fn (array $item, string $code) => [
                'value' => $code,
                'label' => "{$code} — {$item['sihd']}",
            ])
            ->values();

        if ($search !== null && $search !== '') {
            $needle = mb_strtolower($search);
            $options = $options->filter(
                fn (array $option) => str_contains(mb_strtolower($option['label']), $needle)
                    || str_contains(mb_strtolower($option['value']), $needle)
            )->values();
        }

        return $options->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function resumoLookupOptions(?string $search = null): array
    {
        $options = collect(self::MAP)
            ->pluck('resumo')
            ->unique()
            ->sort()
            ->values()
            ->map(fn (string $resumo) => ['value' => $resumo, 'label' => $resumo]);

        if ($search !== null && $search !== '') {
            $needle = mb_strtolower($search);
            $options = $options->filter(
                fn (array $option) => str_contains(mb_strtolower($option['label']), $needle)
            )->values();
        }

        return $options->all();
    }

    /**
     * @return list<string>
     */
    public static function codesForResumo(string $resumo): array
    {
        return collect(self::MAP)
            ->filter(fn (array $item) => $item['resumo'] === $resumo)
            ->keys()
            ->values()
            ->all();
    }

    public static function sqlDescExpression(string $column = 'sa.CARATER_INTERNACAO'): string
    {
        return self::sqlCaseExpression($column, 'sihd');
    }

    public static function sqlResumoExpression(string $column = 'sa.CARATER_INTERNACAO'): string
    {
        return self::sqlCaseExpression($column, 'resumo');
    }

    private static function sqlCaseExpression(string $column, string $key): string
    {
        $cases = collect(self::MAP)
            ->map(function (array $item, string $code) use ($column, $key) {
                $label = str_replace("'", "''", $item[$key]);

                return "WHEN {$column} = '{$code}' THEN '{$label}'";
            })
            ->implode(' ');

        return "CASE {$cases} ELSE CONCAT('Código ', {$column}) END";
    }

    private static function normalizeCode(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $code = trim($code);

        return $code === '' ? null : $code;
    }
}
