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

        $dupMunicipio = $this->markDuplicateTipoAsIndefinido(Cismetro::TIPO_MUNICIPIO);
        $this->line("Duplicados Municipio (tipo_valor=1 → 0): {$dupMunicipio}");

        $dupPrestador = $this->markDuplicateTipoAsIndefinido(Cismetro::TIPO_PRESTADOR);
        $this->line("Duplicados Prestador (tipo_valor=2 → 0): {$dupPrestador}");

        $duplicados = $dupMunicipio + $dupPrestador;
        $indefinidos = DB::table('cismetro')
            ->where('tipo_valor', Cismetro::TIPO_INDEFINIDO)
            ->count();
        $this->line("Total com tipo_valor=0: {$indefinidos}");

        if ($duplicados > 0) {
            $this->warn("{$duplicados} registro(s) extra(s) por codigo marcado(s) como tipo_valor=0");
        }

        $this->info('Classificacao concluida!');

        return self::SUCCESS;
    }

    /**
     * Mantém o 1º registro de cada tipo por codigo; demais viram 0.
     *
     * @param  Collection<int, object{id: int, codigo: string}>|null  $records
     * @return list<int>
     */
    public static function duplicateTipoIdsToZero(int $tipoValor, ?Collection $records = null): array
    {
        $records ??= DB::table('cismetro')
            ->select('id', 'codigo', 'tipo_valor')
            ->where('tipo_valor', $tipoValor)
            ->orderBy('id')
            ->get();

        return $records
            ->groupBy('codigo')
            ->flatMap(fn (Collection $group) => $group->slice(1))
            ->pluck('id')
            ->all();
    }

    private function markDuplicateTipoAsIndefinido(int $tipoValor): int
    {
        $ids = self::duplicateTipoIdsToZero($tipoValor);

        if ($ids === []) {
            return 0;
        }

        return DB::table('cismetro')
            ->whereIn('id', $ids)
            ->update(['tipo_valor' => Cismetro::TIPO_INDEFINIDO]);
    }
}
