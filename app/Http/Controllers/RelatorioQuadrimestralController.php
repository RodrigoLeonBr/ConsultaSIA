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

    public function __construct(private ProducaoQuadrimestralService $service) {}

    public function index(): View
    {
        return view('relatorios.quadrimestral.index', [
            'anos' => $this->service->anosDisponiveis(),
            'quadrimestres' => self::QUADRIMESTRES,
            'resultado' => null,
        ]);
    }

    public function gerar(Request $request): View
    {
        $dados = $request->validate([
            'ano' => 'required|integer|min:1900|max:2199',
            'quadrimestre' => 'required|integer|in:1,2,3',
        ]);

        return view('relatorios.quadrimestral.index', [
            'anos' => $this->service->anosDisponiveis(),
            'quadrimestres' => self::QUADRIMESTRES,
            'resultado' => $this->service->gerar((int) $dados['ano'], (int) $dados['quadrimestre']),
        ]);
    }
}
