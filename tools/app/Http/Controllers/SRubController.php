<?php

namespace App\Http\Controllers;

use App\Models\SRub;
use App\Http\Requests\SRubRequest;
use Illuminate\Http\Request;

class SRubController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SRub::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('rub_id', 'like', "%{$search}%")
                  ->orWhere('rub_dc', 'like', "%{$search}%");
            });
        }

        $srubs = $query->orderBy('rub_id')->paginate(20)->withQueryString();

        return view('srub.index', compact('srubs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('srub.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SRubRequest $request)
    {
        try {
            SRub::create($request->validated());
            
            return redirect()->route('srub.index')
                           ->with('success', 'Fonte de financiamento criada com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Erro ao criar fonte de financiamento: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SRub $srub)
    {
        return view('srub.show', compact('srub'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SRub $srub)
    {
        return view('srub.edit', compact('srub'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SRubRequest $request, SRub $srub)
    {
        try {
            $srub->update($request->validated());
            
            return redirect()->route('srub.index')
                           ->with('success', 'Fonte de financiamento atualizada com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Erro ao atualizar fonte de financiamento: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SRub $srub)
    {
        try {
            $srub->delete();
            
            return redirect()->route('srub.index')
                           ->with('success', 'Fonte de financiamento excluída com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir fonte de financiamento: ' . $e->getMessage());
        }
    }
}