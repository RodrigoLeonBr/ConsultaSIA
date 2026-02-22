<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;

class DistributionChart
{
    protected $chart;

    public function __construct(LarapexChart $chart)
    {
        $this->chart = $chart;
    }

    public function build()
    {
        return $this->chart->donutChart()
            ->setTitle('Distribuição por Prestador')
            ->setSubtitle('Top 5 prestadores')
            ->addData([65, 18, 12, 8, 5])
            ->setLabels(['Hospital Central', 'UBS Norte', 'UBS Sul', 'UBS Leste', 'UBS Oeste'])
            ->setColors(['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6'])
            ->setHeight(250)
            ->setDataLabels(true);
    }
}