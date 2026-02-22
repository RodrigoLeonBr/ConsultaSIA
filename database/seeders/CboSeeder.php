<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CboSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cbos = [
            [
                'cbo' => '225125',
                'ds_cbo' => 'MÉDICO CLÍNICO'
            ],
            [
                'cbo' => '225142',
                'ds_cbo' => 'MÉDICO GINECOLOGISTA E OBSTETRA'
            ],
            [
                'cbo' => '225170',
                'ds_cbo' => 'MÉDICO PEDIATRA'
            ]
        ];

        foreach ($cbos as $cbo) {
            \App\Models\Cbo::updateOrCreate(
                ['cbo' => $cbo['cbo']],
                $cbo
            );
        }

        $this->command->info('CBO records seeded successfully!');
    }
}
