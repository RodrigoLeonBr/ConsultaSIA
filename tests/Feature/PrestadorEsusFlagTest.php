<?php

namespace Tests\Feature;

use App\Models\Prestador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesReportTestUser;
use Tests\TestCase;

class PrestadorEsusFlagTest extends TestCase
{
    use CreatesReportTestUser;
    use RefreshDatabase;

    private function prestador(): Prestador
    {
        return Prestador::create([
            're_cunid' => '2048205',
            're_cnome' => 'NUCLEO DE ESPECIALIDADES',
            're_tipo' => 'U',
            'cnpj' => '12345678000199',
            'area' => 1001,
            'tipouni' => 'M',
        ]);
    }

    public function test_esus_ativo_defaults_to_true(): void
    {
        $prestador = $this->prestador();

        $this->assertTrue($prestador->fresh()->esus_ativo);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            're_cunid' => '2048205',
            're_cnome' => 'NUCLEO DE ESPECIALIDADES',
            're_tipo' => 'U',
            'cnpj' => '12345678000199',
            'area' => 1001,
            'tipouni' => 'M',
            'ativo' => '1',
        ], $override);
    }

    public function test_update_can_disable_esus_ativo(): void
    {
        $prestador = $this->prestador();

        // Sem esus_ativo no payload (checkbox desmarcado) → false.
        $this->actingAs($this->createReportTestUser())
            ->patch(route('prestador.update', $prestador), $this->payload())
            ->assertRedirect(route('prestador.index'));

        $this->assertFalse($prestador->fresh()->esus_ativo);
    }

    public function test_update_can_enable_esus_ativo(): void
    {
        $prestador = $this->prestador();
        $prestador->update(['esus_ativo' => false]);

        $this->actingAs($this->createReportTestUser())
            ->patch(route('prestador.update', $prestador), $this->payload(['esus_ativo' => '1']))
            ->assertRedirect(route('prestador.index'));

        $this->assertTrue($prestador->fresh()->esus_ativo);
    }
}
