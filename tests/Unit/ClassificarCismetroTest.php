<?php

namespace Tests\Unit;

use App\Console\Commands\ClassificarCismetro;
use App\Models\Cismetro;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ClassificarCismetroTest extends TestCase
{
    public function test_duplicate_tipo_ids_to_zero_keeps_first_per_codigo(): void
    {
        $records = new Collection([
            (object) ['id' => 1, 'codigo' => 'AAA', 'tipo_valor' => 2],
            (object) ['id' => 2, 'codigo' => 'AAA', 'tipo_valor' => 2],
            (object) ['id' => 3, 'codigo' => 'BBB', 'tipo_valor' => 1],
            (object) ['id' => 4, 'codigo' => 'BBB', 'tipo_valor' => 1],
        ]);

        $dupPrestador = ClassificarCismetro::duplicateTipoIdsToZero(Cismetro::TIPO_PRESTADOR, $records->where('tipo_valor', 2)->values());
        $dupMunicipio = ClassificarCismetro::duplicateTipoIdsToZero(Cismetro::TIPO_MUNICIPIO, $records->where('tipo_valor', 1)->values());

        $this->assertSame([2], $dupPrestador);
        $this->assertSame([4], $dupMunicipio);
    }

    public function test_tipo_indefinido_label_reflects_duplicate_semantics(): void
    {
        $cismetro = new Cismetro(['tipo_valor' => Cismetro::TIPO_INDEFINIDO]);

        $this->assertSame('Duplicado / Revisar', $cismetro->tipo_valor_label);
    }
}
