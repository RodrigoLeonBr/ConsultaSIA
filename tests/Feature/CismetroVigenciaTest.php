<?php

namespace Tests\Feature;

use App\Models\Cismetro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CismetroVigenciaTest extends TestCase
{
    use RefreshDatabase;

    private function makeCismetro(array $overrides = []): Cismetro
    {
        return Cismetro::create(array_merge([
            'codigo' => '0202031039',
            'credenciamento' => 'Credencimento 2023',
            'grupo' => 'EXAMES',
            'descricao' => 'Ige Especifico',
            'valor' => 61.00,
            'tipo_valor' => Cismetro::TIPO_MUNICIPIO,
            'competencia_inicial' => '202301',
            'competencia_final' => '999999',
        ], $overrides));
    }

    public function test_vigencia_columns_have_defaults(): void
    {
        $c = Cismetro::create([
            'codigo' => '0202031039',
            'credenciamento' => 'x',
            'grupo' => 'g',
            'descricao' => 'd',
            'valor' => 10.0,
        ]);

        $this->assertSame('202301', $c->fresh()->competencia_inicial);
        $this->assertSame('999999', $c->fresh()->competencia_final);
    }

    public function test_scope_vigente_filters_by_competencia(): void
    {
        // Versão 2023 fechada em 202407, versão 2024 aberta a partir de 202408.
        $v2023 = $this->makeCismetro(['valor' => 61.00, 'competencia_inicial' => '202301', 'competencia_final' => '202407']);
        $v2024 = $this->makeCismetro(['valor' => 70.00, 'competencia_inicial' => '202408', 'competencia_final' => '999999']);

        $vigenteJul = Cismetro::vigente('202407')->pluck('id');
        $vigenteAgo = Cismetro::vigente('202408')->pluck('id');
        $vigenteDez = Cismetro::vigente('202412')->pluck('id');

        $this->assertEquals([$v2023->id], $vigenteJul->all());
        $this->assertEquals([$v2024->id], $vigenteAgo->all());
        $this->assertEquals([$v2024->id], $vigenteDez->all());
    }
}
