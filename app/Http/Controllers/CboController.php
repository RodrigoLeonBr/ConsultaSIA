<?php

namespace App\Http\Controllers;

use App\Models\Cbo;
use App\Http\Requests\CboRequest;
use Illuminate\Http\Request;

class CboController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Cbo::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cbo', 'like', "%{$search}%")
                  ->orWhere('ds_cbo', 'like', "%{$search}%");
            });
        }

        $cbos = $query->orderBy('cbo')->paginate(20)->withQueryString();

        return view('cbo.index', compact('cbos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cbo.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CboRequest $request)
    {
        try {
            Cbo::create($request->validated());
            
            return redirect()->route('cbo.index')
                           ->with('success', 'CBO criado com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Erro ao criar CBO: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($cbo)
    {
        $cboModel = Cbo::where('CBO', $cbo)->firstOrFail();
        $cboModel->load('sPrds');
        return view('cbo.show', ['cbo' => $cboModel]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($cbo)
    {
        $cboModel = Cbo::where('CBO', $cbo)->firstOrFail();
        return view('cbo.edit', ['cbo' => $cboModel]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CboRequest $request, $cbo)
    {
        try {
            $cboModel = Cbo::where('CBO', $cbo)->firstOrFail();
            $cboModel->update($request->validated());
            
            return redirect()->route('cbo.index')
                           ->with('success', 'CBO atualizado com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Erro ao atualizar CBO: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($cbo)
    {
        try {
            $cboModel = Cbo::where('CBO', $cbo)->firstOrFail();
            
            // Check if CBO has related records
            if ($cboModel->sPrds()->count() > 0) {
                return back()->with('error', 'Não é possível excluir este CBO pois possui registros relacionados.');
            }

            $cboModel->delete();
            
            return redirect()->route('cbo.index')
                           ->with('success', 'CBO excluído com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir CBO: ' . $e->getMessage());
        }
    }
}