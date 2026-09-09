<?php

namespace Database\Factories;

use App\Models\Cismetro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cismetro>
 */
class CismetroFactory extends Factory
{
    protected $model = Cismetro::class;

    public function definition(): array
    {
        return [
            'codigo' => str_pad((string) $this->faker->numberBetween(1, 9_999_999_999), 10, '0', STR_PAD_LEFT),
            'credenciamento' => 'Credenciamento 2024',
            'grupo' => $this->faker->randomElement(['EXAMES', 'CIRURGIA', 'ANESTESIOLOGIA']),
            'descricao' => $this->faker->sentence(3),
            'valor' => $this->faker->randomFloat(2, 1, 5000),
            'tipo_valor' => Cismetro::TIPO_MUNICIPIO,
            'competencia_inicial' => '202408',
            'competencia_final' => '999999',
        ];
    }
}
