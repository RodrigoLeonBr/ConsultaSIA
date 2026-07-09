<?php

namespace Tests\Unit;

use App\Models\Cismetro;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CismetroTipoValorTest extends TestCase
{
    #[DataProvider('tipoValorLabelProvider')]
    public function test_tipo_valor_label_returns_expected_text(int $tipoValor, string $expectedLabel): void
    {
        $cismetro = new Cismetro(['tipo_valor' => $tipoValor]);

        $this->assertSame($expectedLabel, $cismetro->tipo_valor_label);
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function tipoValorLabelProvider(): array
    {
        return [
            'municipio' => [Cismetro::TIPO_MUNICIPIO, 'Município / Geral'],
            'prestador' => [Cismetro::TIPO_PRESTADOR, 'Prestador'],
            'indefinido' => [Cismetro::TIPO_INDEFINIDO, 'Duplicado / Revisar'],
        ];
    }

    public function test_tipo_valor_options_contains_all_values(): void
    {
        $this->assertSame(
            [Cismetro::TIPO_MUNICIPIO, Cismetro::TIPO_PRESTADOR, Cismetro::TIPO_INDEFINIDO],
            array_keys(Cismetro::tipoValorOptions()),
        );
    }
}
