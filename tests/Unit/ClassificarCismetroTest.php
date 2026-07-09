<?php

namespace Tests\Unit;

use App\Console\Commands\ClassificarCismetro;
use App\Models\Cismetro;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ClassificarCismetroTest extends TestCase
{
    public function test_duplicate_padrao_ids_to_zero_keeps_first_and_prestador(): void
    {
        $records = new Collection([
            (object) ['id' => 1, 'codigo' => 'AAA'],
            (object) ['id' => 2, 'codigo' => 'AAA'],
            (object) ['id' => 3, 'codigo' => 'BBB'],
            (object) ['id' => 4, 'codigo' => 'BBB'],
            (object) ['id' => 5, 'codigo' => 'BBB'],
        ]);

        $ids = ClassificarCismetro::duplicatePadraoIdsToZero($records);

        $this->assertSame([2, 4, 5], $ids);
    }

    public function test_tipo_indefinido_label_reflects_duplicate_semantics(): void
    {
        $cismetro = new Cismetro(['tipo_valor' => Cismetro::TIPO_INDEFINIDO]);

        $this->assertSame('Duplicado / Revisar', $cismetro->tipo_valor_label);
    }
}
