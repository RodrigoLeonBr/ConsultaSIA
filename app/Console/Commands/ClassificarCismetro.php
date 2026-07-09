<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClassificarCismetro extends Command
{
    protected $signature = 'cismetro:classificar';

    protected $description = 'Classifica registros da cismetro com tipo_valor baseado na descricao (1=Municipio, 2=Prestador, 0=Revisar)';

    public function handle(): int
    {
        $this->info('Classificando registros da cismetro...');
        $total = DB::table('cismetro')->count();
        $this->line("Total de registros: {$total}");

        // ponytail: default da migration é 1 — zerar antes para a "sobra" virar 0
        DB::table('cismetro')->update(['tipo_valor' => 0]);

        $municipio = DB::table('cismetro')
            ->where(function ($query) {
                $query->whereRaw('UPPER(descricao) LIKE ?', ['%MUNICÍPIO%'])
                    ->orWhereRaw('UPPER(descricao) LIKE ?', ['%MUNICIPIO%']);
            })
            ->update(['tipo_valor' => 1]);
        $this->line("Classificados como Municipio (tipo_valor=1): {$municipio}");

        $prestador = DB::table('cismetro')
            ->where('tipo_valor', 0)
            ->whereRaw('UPPER(descricao) LIKE ?', ['%PRESTADOR%'])
            ->update(['tipo_valor' => 2]);
        $this->line("Classificados como Prestador (tipo_valor=2): {$prestador}");

        $excecoes = DB::table('cismetro')
            ->where('tipo_valor', 0)
            ->count();
        $this->line("Classificados como Indefinido (tipo_valor=0): {$excecoes}");

        if ($excecoes > 0) {
            $this->warn("{$excecoes} registro(s) marcado(s) como tipo_valor=0 (revisao manual necessaria)");
        }

        $this->info('Classificacao concluida!');

        return self::SUCCESS;
    }
}
