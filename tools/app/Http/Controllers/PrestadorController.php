<?php

namespace App\Http\Controllers;

use App\Models\Prestador;
use App\Http\Requests\PrestadorRequest;
use Illuminate\Http\Request;

class PrestadorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Prestador::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('re_cunid', 'like', "%{$search}%")
                  ->orWhere('re_cnome', 'like', "%{$search}%")
                  ->orWhere('cnpj', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('tipo')) {
            $query->where('re_tipo', $request->tipo);
        }

        // Filter by status
        if ($request->filled('ativo')) {
            $query->where('ativo', $request->ativo === '1');
        }

        // Filter by unit type
        if ($request->filled('tipouni')) {
            $query->where('tipouni', $request->tipouni);
        }

        $prestadores = $query->orderBy('re_cnome')->paginate(20)->withQueryString();

        return view('prestador.index', compact('prestadores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('prestador.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PrestadorRequest $request)
    {
        try {
            Prestador::create($request->validated());
            
            return redirect()->route('prestador.index')
                           ->with('success', 'Prestador criado com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Erro ao criar prestador: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Prestador $prestador)
    {
        $prestador->load('sPrds');
        return view('prestador.show', compact('prestador'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Prestador $prestador)
    {
        return view('prestador.edit', compact('prestador'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PrestadorRequest $request, Prestador $prestador)
    {
        try {
            $prestador->update($request->validated());
            
            return redirect()->route('prestador.index')
                           ->with('success', 'Prestador atualizado com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Erro ao atualizar prestador: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Prestador $prestador)
    {
        try {
            // Check if Prestador has related records
            if ($prestador->sPrds()->count() > 0) {
                return back()->with('error', 'Não é possível excluir este prestador pois possui registros relacionados.');
            }

            $prestador->delete();
            
            return redirect()->route('prestador.index')
                           ->with('success', 'Prestador excluído com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir prestador: ' . $e->getMessage());
        }
    }

    /**
     * Toggle prestador status (ativo/inativo).
     */
    public function toggleStatus(Prestador $prestador)
    {
        try {
            $prestador->update(['ativo' => !$prestador->ativo]);
            
            $status = $prestador->ativo ? 'ativado' : 'desativado';
            return back()->with('success', "Prestador {$status} com sucesso!");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao alterar status: ' . $e->getMessage());
        }
    }
}