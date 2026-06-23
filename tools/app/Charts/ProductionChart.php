<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;

class ProductionChart
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build()
    {
        // Dados simulados para produção dos últimos 30 dias
        $data = [45, 52, 48, 61, 55, 67, 72, 68, 75, 82, 78, 85, 92, 88, 95, 102, 98, 105, 112, 108, 115, 122, 118, 125, 132, 128, 135, 142, 138, 145];
        
        // Labels para os últimos 30 dias
        $labels = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');
        }

        return $this->chart->lineChart()
            ->setTitle('Produção dos Últimos 30 Dias')
            ->setSubtitle('Número de procedimentos realizados')
            ->addData('Procedimentos', $data)
            ->setXAxis($labels)
            ->setColors(['#3B82F6'])
            ->setHeight(250)
            ->setGrid(true)
            ->setMarkers(['#3B82F6'], 4, 10);
    }
}