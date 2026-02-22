<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcedimentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Assumir que a tabela já existe

        $procedimentos = [
            // Consultas Médicas
            ['codigo' => '0301010019', 'procedimento' => 'CONSULTA MÉDICA EM ATENÇÃO BÁSICA', 'pa_total' => 10.00, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030101001', 'financiamento' => 'PISO DE ATENÇÃO BÁSICA FIXO'],
            ['codigo' => '0301010027', 'procedimento' => 'CONSULTA MÉDICA EM ATENÇÃO ESPECIALIZADA', 'pa_total' => 15.00, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030101002', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0301010035', 'procedimento' => 'CONSULTA MÉDICA DE URGÊNCIA', 'pa_total' => 12.00, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030101003', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],

            // Consultas de Enfermagem
            ['codigo' => '0301020013', 'procedimento' => 'CONSULTA DE ENFERMAGEM', 'pa_total' => 8.00, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030102001', 'financiamento' => 'PISO DE ATENÇÃO BÁSICA FIXO'],
            ['codigo' => '0301020021', 'procedimento' => 'CONSULTA DE ENFERMAGEM EM PUERICULTURA', 'pa_total' => 8.50, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030102002', 'financiamento' => 'PISO DE ATENÇÃO BÁSICA FIXO'],

            // Procedimentos Diagnósticos
            ['codigo' => '0202010015', 'procedimento' => 'RADIOGRAFIA DE TÓRAX', 'pa_total' => 4.50, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020201001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0202010023', 'procedimento' => 'RADIOGRAFIA DE ABDOME', 'pa_total' => 4.50, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020201002', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0202010031', 'procedimento' => 'RADIOGRAFIA DE MEMBROS', 'pa_total' => 4.50, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020201003', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],

            // Exames Laboratoriais
            ['codigo' => '0202020011', 'procedimento' => 'HEMOGRAMA COMPLETO', 'pa_total' => 4.11, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020202001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0202020029', 'procedimento' => 'GLICEMIA DE JEJUM', 'pa_total' => 2.02, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020202002', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0202020037', 'procedimento' => 'COLESTEROL TOTAL', 'pa_total' => 2.02, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020202003', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0202020045', 'procedimento' => 'TRIGLICERÍDEOS', 'pa_total' => 2.02, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020202004', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0202020053', 'procedimento' => 'UREIA', 'pa_total' => 1.83, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020202005', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0202020061', 'procedimento' => 'CREATININA', 'pa_total' => 1.83, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020202006', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],

            // Ultrassonografias
            ['codigo' => '0202040011', 'procedimento' => 'ULTRASSONOGRAFIA DE ABDOME TOTAL', 'pa_total' => 23.50, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020204001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0202040029', 'procedimento' => 'ULTRASSONOGRAFIA PÉLVICA', 'pa_total' => 18.86, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020204002', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0202040037', 'procedimento' => 'ULTRASSONOGRAFIA OBSTÉTRICA', 'pa_total' => 23.50, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020204003', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],

            // Tomografias
            ['codigo' => '0202050017', 'procedimento' => 'TOMOGRAFIA COMPUTADORIZADA DE CRÂNIO', 'pa_total' => 67.20, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020205001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0202050025', 'procedimento' => 'TOMOGRAFIA COMPUTADORIZADA DE TÓRAX', 'pa_total' => 67.20, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020205002', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0202050033', 'procedimento' => 'TOMOGRAFIA COMPUTADORIZADA DE ABDOME', 'pa_total' => 67.20, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '020205003', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],

            // Cirurgias Gerais
            ['codigo' => '0404010010', 'procedimento' => 'APENDICECTOMIA', 'pa_total' => 284.31, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '040401001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0404010028', 'procedimento' => 'COLECISTECTOMIA', 'pa_total' => 368.40, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '040401002', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0404010036', 'procedimento' => 'HERNIORRAFIA INGUINAL', 'pa_total' => 184.20, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '040401003', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],

            // Partos
            ['codigo' => '0411010026', 'procedimento' => 'PARTO NORMAL', 'pa_total' => 290.00, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '041101002', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0411020021', 'procedimento' => 'PARTO CESARIANO', 'pa_total' => 406.50, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '041102002', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],

            // Procedimentos de Enfermagem
            ['codigo' => '0301050017', 'procedimento' => 'CURATIVO GRAU I', 'pa_total' => 3.20, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030105001', 'financiamento' => 'PISO DE ATENÇÃO BÁSICA FIXO'],
            ['codigo' => '0301050025', 'procedimento' => 'CURATIVO GRAU II', 'pa_total' => 5.40, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030105002', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0301050033', 'procedimento' => 'ADMINISTRAÇÃO DE MEDICAMENTOS', 'pa_total' => 0.63, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030105003', 'financiamento' => 'PISO DE ATENÇÃO BÁSICA FIXO'],

            // Vacinas
            ['codigo' => '0301080019', 'procedimento' => 'VACINA BCG', 'pa_total' => 0.63, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030108001', 'financiamento' => 'PISO DE ATENÇÃO BÁSICA FIXO'],
            ['codigo' => '0301080027', 'procedimento' => 'VACINA HEPATITE B', 'pa_total' => 0.63, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030108002', 'financiamento' => 'PISO DE ATENÇÃO BÁSICA FIXO'],
            ['codigo' => '0301080035', 'procedimento' => 'VACINA TRÍPLICE VIRAL', 'pa_total' => 0.63, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030108003', 'financiamento' => 'PISO DE ATENÇÃO BÁSICA FIXO'],

            // Fisioterapia
            ['codigo' => '0301110015', 'procedimento' => 'ATENDIMENTO FISIOTERAPÊUTICO', 'pa_total' => 7.50, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030111001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0301110023', 'procedimento' => 'FISIOTERAPIA RESPIRATÓRIA', 'pa_total' => 7.50, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030111002', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],

            // Psicologia
            ['codigo' => '0301120010', 'procedimento' => 'ATENDIMENTO PSICOLÓGICO INDIVIDUAL', 'pa_total' => 7.50, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030112001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0301120028', 'procedimento' => 'ATENDIMENTO PSICOLÓGICO EM GRUPO', 'pa_total' => 3.75, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030112002', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],

            // Nutrição
            ['codigo' => '0301130015', 'procedimento' => 'CONSULTA NUTRICIONAL', 'pa_total' => 7.50, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030113001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],

            // Fonoaudiologia
            ['codigo' => '0301140010', 'procedimento' => 'ATENDIMENTO FONOAUDIOLÓGICO', 'pa_total' => 7.50, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030114001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],

            // Terapia Ocupacional
            ['codigo' => '0301150015', 'procedimento' => 'ATENDIMENTO DE TERAPIA OCUPACIONAL', 'pa_total' => 7.50, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030115001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],

            // Odontologia
            ['codigo' => '0307010010', 'procedimento' => 'CONSULTA ODONTOLÓGICA', 'pa_total' => 2.83, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030701001', 'financiamento' => 'PISO DE ATENÇÃO BÁSICA FIXO'],
            ['codigo' => '0307020015', 'procedimento' => 'RESTAURAÇÃO DENTÁRIA', 'pa_total' => 3.30, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030702001', 'financiamento' => 'PISO DE ATENÇÃO BÁSICA FIXO'],
            ['codigo' => '0307030010', 'procedimento' => 'EXODONTIA SIMPLES', 'pa_total' => 3.30, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030703001', 'financiamento' => 'PISO DE ATENÇÃO BÁSICA FIXO'],

            // Internações
            ['codigo' => '0303010010', 'procedimento' => 'INTERNAÇÃO CLÍNICA', 'pa_total' => 35.00, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030301001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0303020015', 'procedimento' => 'INTERNAÇÃO CIRÚRGICA', 'pa_total' => 45.00, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030302001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0303030010', 'procedimento' => 'INTERNAÇÃO OBSTÉTRICA', 'pa_total' => 40.00, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030303001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0303040015', 'procedimento' => 'INTERNAÇÃO PEDIÁTRICA', 'pa_total' => 38.00, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030304001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],

            // UTI
            ['codigo' => '0303050010', 'procedimento' => 'DIÁRIA DE UTI ADULTO', 'pa_total' => 315.00, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030305001', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0303050028', 'procedimento' => 'DIÁRIA DE UTI PEDIÁTRICA', 'pa_total' => 420.00, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030305002', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
            ['codigo' => '0303050036', 'procedimento' => 'DIÁRIA DE UTI NEONATAL', 'pa_total' => 525.00, 'rub_total' => '01', 'rub_dc' => 'TESOURO NACIONAL', 'pa_rub' => '01', 'pa_id' => '030305003', 'financiamento' => 'MÉDIA E ALTA COMPLEXIDADE'],
        ];

        $this->command->info('Inserindo ' . count($procedimentos) . ' procedimentos...');

        // Inserir em lotes para melhor performance
        $chunks = array_chunk($procedimentos, 25);
        foreach ($chunks as $chunk) {
            foreach ($chunk as $procedimento) {
                DB::table('procedimento')->updateOrInsert(
                    ['codigo' => $procedimento['codigo']],
                    $procedimento
                );
            }
        }

        $this->command->info('Procedimento records seeded successfully! Total: ' . count($procedimentos) . ' registros');
    }
}
