<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SRubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rubrics = [
            [
                'rub_id' => '01',
                'rub_dc' => 'TESOURO NACIONAL',
                'rub_total' => ''
            ],
            [
                'rub_id' => '02',
                'rub_dc' => 'RECURSOS PRÓPRIOS',
                'rub_total' => ''
            ],
            [
                'rub_id' => '03',
                'rub_dc' => 'CONVÊNIOS',
                'rub_total' => ''
            ]
        ];

        foreach ($rubrics as $rubric) {
            \App\Models\SRub::updateOrCreate(
                ['rub_id' => $rubric['rub_id']],
                $rubric
            );
        }

        $this->command->info('S_Rub records seeded successfully!');
    }
}
