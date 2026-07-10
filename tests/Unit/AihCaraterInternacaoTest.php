<?php

namespace Tests\Unit;

use App\Support\AihCaraterInternacao;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AihCaraterInternacaoTest extends TestCase
{
    #[DataProvider('labelProvider')]
    public function test_label_returns_sihd_description(string $code, string $expected): void
    {
        $this->assertSame($expected, AihCaraterInternacao::label($code));
    }

    #[DataProvider('resumoProvider')]
    public function test_resumo_returns_dashboard_group(string $code, string $expected): void
    {
        $this->assertSame($expected, AihCaraterInternacao::resumo($code));
    }

    public function test_codes_for_resumo_groups_accident_codes_together(): void
    {
        $this->assertSame(['03', '04', '05'], AihCaraterInternacao::codesForResumo('Urgência (Acidente)'));
    }

    public function test_lookup_options_include_all_known_codes(): void
    {
        $this->assertCount(6, AihCaraterInternacao::lookupOptions());
    }

    public function test_resumo_lookup_options_are_unique(): void
    {
        $this->assertSame(
            ['Eletivo', 'Obstetrícia', 'Urgência (Acidente)', 'Urgência / Emergência'],
            array_column(AihCaraterInternacao::resumoLookupOptions(), 'value'),
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function labelProvider(): array
    {
        return [
            'eletivo' => ['01', 'Eletivo'],
            'urgencia' => ['02', 'Urgência'],
            'acidente_trajeto' => ['03', 'Acidente no trajeto para o trabalho'],
            'obstetricia' => ['06', 'Pós-parto normal / cesárea'],
            'desconhecido' => ['99', 'Código 99'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function resumoProvider(): array
    {
        return [
            'eletivo' => ['01', 'Eletivo'],
            'urgencia' => ['02', 'Urgência / Emergência'],
            'acidente_trabalho' => ['04', 'Urgência (Acidente)'],
            'obstetricia' => ['06', 'Obstetrícia'],
        ];
    }
}
