<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;

class ProceduresChart
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build()
    {
        return $this->chart->horizontalBarChart()
            ->setTitle('Top 5 Procedimentos')
            ->setSubtitle('Procedimentos mais realizados')
            ->addData('Quantidade', [1250, 980, 850, 720, 650])
            ->setXAxis(['Consulta Médica', 'Exame Laboratorial', 'Raio-X', 'Ultrassom', 'Tomografia'])
            ->setColors(['#3B82F6'])
            ->setHeight(300)
            ->setDataLabels(true)
            ->setGrid(true);
    }
}