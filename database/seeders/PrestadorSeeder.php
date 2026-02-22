<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PrestadorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prestadores = [
            [
                're_cunid' => '0000001',
                're_cnome' => 'HOSPITAL MUNICIPAL',
                're_tipo' => 'J',
                'cnpj' => '12345678000199',
                'area' => 1,
                'tipouni' => 'H',
                'relatorio' => null,
                'ativo' => true
            ],
            [
                're_cunid' => '0000002',
                're_cnome' => 'CLÍNICA MÉDICA LTDA',
                're_tipo' => 'J',
                'cnpj' => '98765432000188',
                'area' => 2,
                'tipouni' => 'C',
                'relatorio' => null,
                'ativo' => true
            ]
        ];

        foreach ($prestadores as $prestador) {
            \App\Models\Prestador::updateOrCreate(
                ['re_cunid' => $prestador['re_cunid']],
                $prestador
            );
        }

        $this->command->info('Prestador records seeded successfully!');
    }
}
