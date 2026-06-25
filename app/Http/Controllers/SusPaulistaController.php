<?php

namespace App\Http\Controllers;

use App\Models\SusPaulista;
use Illuminate\Http\Request;

class SusPaulistaController extends Controller
{
    public function index(Request $request)
    {
        $query = SusPaulista::query();

        if ($request->filled('modalidade')) {
            $query->where('modalidade', $request->modalidade);
        }

        if ($request->boolean('somente_vigentes', true)) {
            $query->active();
        }

        if ($request->filled('competencia')) {
            $query->forCompetencia($request->competencia);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                    ->orWhere('descricao', 'like', "%{$search}%");
            });
        }

        $orderBy = $request->get('order_by', 'codigo');
        $orderDirection = in_array(strtolower($request->get('order_direction', 'asc')), ['asc', 'desc'], true)
            ? $request->get('order_direction', 'asc')
            : 'asc';

        if (in_array($orderBy, ['codigo', 'descricao', 'tab_paulista', 'complementacao_tsp', 'competencia_inicial'], true)) {
            $query->orderBy($orderBy, $orderDirection);
        }

        $registros = $query->paginate(20)->withQueryString();

        return view('sus-paulista.index', compact('registros'));
    }
}
