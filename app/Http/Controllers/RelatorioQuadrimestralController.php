<?php

namespace App\Http\Controllers;

use App\Services\ProducaoQuadrimestralService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RelatorioQuadrimestralController extends Controller
{
    private const QUADRIMESTRES = [
        1 => '1º Quadrimestre (Jan–Abr)',
        2 => '2º Quadrimestre (Mai–Ago)',
        3 => '3º Quadrimestre (Set–Dez)',
    ];

    private const VISOES = [
        'meses' => 'Meses do quadrimestre',
        'anos' => 'Comparação anual (4 anos)',
    ];

    public function __construct(private ProducaoQuadrimestralService $service) {}

    public function index(): View
    {
        return view('relatorios.quadrimestral.index', [
            'anos' => $this->service->anosDisponiveis(),
            'quadrimestres' => self::QUADRIMESTRES,
            'visoes' => self::VISOES,
            'resultado' => null,
        ]);
    }

    public function gerar(Request $request): View
    {
        $dados = $request->validate([
            'ano' => 'required|integer|min:1900|max:2199',
            'quadrimestre' => 'required|integer|in:1,2,3',
            'modo' => 'nullable|in:meses,anos',
        ]);

        // ponytail: modo 'anos' varre 16 competências do s_prd (~4x); evita corte por max_execution_time.
        set_time_limit(120);

        return view('relatorios.quadrimestral.index', [
            'anos' => $this->service->anosDisponiveis(),
            'quadrimestres' => self::QUADRIMESTRES,
            'visoes' => self::VISOES,
            'resultado' => $this->service->gerar((int) $dados['ano'], (int) $dados['quadrimestre'], $dados['modo'] ?? 'meses'),
        ]);
    }
}
