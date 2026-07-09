<?php

namespace App\Http\Controllers;

use App\Http\Requests\CismetroRequest;
use App\Models\Cismetro;
use Illuminate\Http\Request;

class CismetroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Cismetro::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                    ->orWhere('descricao', 'like', "%{$search}%")
                    ->orWhere('grupo', 'like', "%{$search}%")
                    ->orWhere('credenciamento', 'like', "%{$search}%");
            });
        }

        // Filter by grupo
        if ($request->filled('grupo')) {
            $query->where('grupo', $request->grupo);
        }

        // Filter by credenciamento
        if ($request->filled('credenciamento')) {
            $query->where('credenciamento', 'like', "%{$request->credenciamento}%");
        }

        if ($request->filled('tipo_valor') && in_array((int) $request->tipo_valor, [0, 1, 2], true)) {
            $query->where('tipo_valor', (int) $request->tipo_valor);
        }

        // Order by
        $orderBy = $request->get('order_by', 'codigo');
        $orderDirection = $request->get('order_direction', 'asc');

        // Sanitize order direction to prevent SQL injection
        $orderDirection = in_array(strtolower($orderDirection), ['asc', 'desc']) ? $orderDirection : 'asc';

        if (in_array($orderBy, ['codigo', 'descricao', 'valor', 'grupo', 'credenciamento', 'tipo_valor'])) {
            $query->orderBy($orderBy, $orderDirection);
        }

        $cismetros = $query->paginate(20)->withQueryString();

        return view('cismetro.index', compact('cismetros'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cismetro.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CismetroRequest $request)
    {
        try {
            Cismetro::create($request->validated());

            return redirect()->route('cismetro.index')
                ->with('success', 'Cismetro criado com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Erro ao criar cismetro: '.$e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Cismetro $cismetro)
    {
        return view('cismetro.show', compact('cismetro'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cismetro $cismetro)
    {
        return view('cismetro.edit', compact('cismetro'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CismetroRequest $request, Cismetro $cismetro)
    {
        try {
            $cismetro->update($request->validated());

            return redirect()->route('cismetro.index')
                ->with('success', 'Cismetro atualizado com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Erro ao atualizar cismetro: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cismetro $cismetro)
    {
        try {
            // Check if Cismetro has related records
            if ($cismetro->sPrds()->count() > 0) {
                return back()->with('error', 'Não é possível excluir este cismetro pois possui registros relacionados.');
            }

            if ($cismetro->sPaps()->count() > 0) {
                return back()->with('error', 'Não é possível excluir este cismetro pois possui registros relacionados.');
            }

            $cismetro->delete();

            return redirect()->route('cismetro.index')
                ->with('success', 'Cismetro excluído com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir cismetro: '.$e->getMessage());
        }
    }
}
