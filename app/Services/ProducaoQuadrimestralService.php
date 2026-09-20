<?php

namespace App\Services;

class ProducaoQuadrimestralService
{
    private const MESES = [
        '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
        '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
        '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez',
    ];

    /** @return array<int,string> 4 competências YYYYMM */
    public function competencias(int $ano, int $quadrimestre): array
    {
        $inicio = ($quadrimestre - 1) * 4 + 1;
        $out = [];
        for ($m = $inicio; $m < $inicio + 4; $m++) {
            $out[] = sprintf('%04d%02d', $ano, $m);
        }

        return $out;
    }

    /**
     * @param  array<int,string>  $competencias  YYYYMM
     * @return array<int,string> rótulos "Mmm/AAAA"
     */
    public function rotulosMeses(array $competencias): array
    {
        return array_map(
            fn (string $c): string => self::MESES[substr($c, 4, 2)].'/'.substr($c, 0, 4),
            $competencias
        );
    }
}
