<?php

namespace App\Http\Controllers;

use App\Models\Procedimento;
use App\Http\Requests\ProcedimentoRequest;
use Illuminate\Http\Request;

class ProcedimentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Procedimento::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhere('procedimento', 'like', "%{$search}%")
                  ->orWhere('financiamento', 'like', "%{$search}%");
            });
        }

        // Filter by financiamento
        if ($request->filled('financiamento')) {
            $query->where('financiamento', $request->financiamento);
        }

        // Order by
        $orderBy = $request->get('order_by', 'codigo');
        $orderDirection = $request->get('order_direction', 'asc');
        
        // Sanitize order direction to prevent SQL injection
        $orderDirection = in_array(strtolower($orderDirection), ['asc', 'desc']) ? $orderDirection : 'asc';
        
        if (in_array($orderBy, ['codigo', 'procedimento', 'pa_total', 'financiamento'])) {
            $query->orderBy($orderBy, $orderDirection);
        }

        $procedimentos = $query->with('cismetros')->paginate(20)->withQueryString();

        return view('procedimento.index', compact('procedimentos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('procedimento.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProcedimentoRequest $request)
    {
        try {
            Procedimento::create($request->validated());
            
            return redirect()->route('procedimento.index')
                           ->with('success', 'Procedimento criado com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Erro ao criar procedimento: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Procedimento $procedimento)
    {
        $procedimento->load(['sPrds', 'cismetros']);
        return view('procedimento.show', compact('procedimento'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Procedimento $procedimento)
    {
        return view('procedimento.edit', compact('procedimento'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProcedimentoRequest $request, Procedimento $procedimento)
    {
        try {
            $procedimento->update($request->validated());
            
            return redirect()->route('procedimento.index')
                           ->with('success', 'Procedimento atualizado com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Erro ao atualizar procedimento: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Procedimento $procedimento)
    {
        try {
            // Check if Procedimento has related records
            if ($procedimento->sPrds()->count() > 0) {
                return back()->with('error', 'Não é possível excluir este procedimento pois possui registros relacionados.');
            }

            $procedimento->delete();
            
            return redirect()->route('procedimento.index')
                           ->with('success', 'Procedimento excluído com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir procedimento: ' . $e->getMessage());
        }
    }
}