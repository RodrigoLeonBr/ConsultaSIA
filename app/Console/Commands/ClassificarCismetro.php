<?php

namespace App\Console\Commands;

use App\Models\Cismetro;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClassificarCismetro extends Command
{
    protected $signature = 'cismetro:classificar';

    protected $description = 'Classifica tipo_valor da cismetro (1=padrao/municipio, 2=prestador, 0=duplicado)';

    public function handle(): int
    {
        $this->info('Classificando registros da cismetro...');
        $total = DB::table('cismetro')->count();
        $this->line("Total de registros: {$total}");

        DB::table('cismetro')->update(['tipo_valor' => Cismetro::TIPO_MUNICIPIO]);

        $municipio = DB::table('cismetro')
            ->where(function ($query) {
                $query->whereRaw('UPPER(descricao) LIKE ?', ['%MUNICÍPIO%'])
                    ->orWhereRaw('UPPER(descricao) LIKE ?', ['%MUNICIPIO%']);
            })
            ->update(['tipo_valor' => Cismetro::TIPO_MUNICIPIO]);
        $this->line("Classificados como Municipio (tipo_valor=1): {$municipio}");

        $prestador = DB::table('cismetro')
            ->where(function ($query) {
                $query->whereRaw('UPPER(descricao) NOT LIKE ?', ['%MUNICÍPIO%'])
                    ->whereRaw('UPPER(descricao) NOT LIKE ?', ['%MUNICIPIO%']);
            })
            ->whereRaw('UPPER(descricao) LIKE ?', ['%PRESTADOR%'])
            ->update(['tipo_valor' => Cismetro::TIPO_PRESTADOR]);
        $this->line("Classificados como Prestador (tipo_valor=2): {$prestador}");

        $duplicados = $this->markDuplicatePadraoAsIndefinido();
        $this->line("Marcados como duplicado (tipo_valor=0): {$duplicados}");

        $indefinidos = DB::table('cismetro')
            ->where('tipo_valor', Cismetro::TIPO_INDEFINIDO)
            ->count();
        $this->line("Total com tipo_valor=0: {$indefinidos}");

        if ($duplicados > 0) {
            $this->warn("{$duplicados} registro(s) com codigo repetido marcado(s) como tipo_valor=0");
        }

        $this->info('Classificacao concluida!');

        return self::SUCCESS;
    }

    /**
     * ponytail: zera apenas o 2º+ registro por codigo que ainda está no padrao (1);
     * pares municipio/prestador (tipo 2) não são afetados.
     *
     * @param  Collection<int, object{id: int, codigo: string}>|null  $records
     */
    public static function duplicatePadraoIdsToZero(?Collection $records = null): array
    {
        $records ??= DB::table('cismetro')
            ->select('id', 'codigo')
            ->where('tipo_valor', Cismetro::TIPO_MUNICIPIO)
            ->orderBy('id')
            ->get();

        return $records
            ->groupBy('codigo')
            ->flatMap(fn (Collection $group) => $group->slice(1))
            ->pluck('id')
            ->all();
    }

    private function markDuplicatePadraoAsIndefinido(): int
    {
        $ids = self::duplicatePadraoIdsToZero();

        if ($ids === []) {
            return 0;
        }

        return DB::table('cismetro')
            ->whereIn('id', $ids)
            ->update(['tipo_valor' => Cismetro::TIPO_INDEFINIDO]);
    }
}
