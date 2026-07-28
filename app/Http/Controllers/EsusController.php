<?php

namespace App\Http\Controllers;

use App\Http\Requests\SEsusRequest;
use App\Models\SEsus;
use Illuminate\Http\Request;

class EsusController extends Controller
{
    public function index(Request $request)
    {
        $query = SEsus::query();

        if ($request->filled('competencia')) {
            $query->where('competencia', $request->competencia);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('unidade', 'like', "%{$search}%")
                    ->orWhere('cnes', 'like', "%{$search}%")
                    ->orWhere('codigo_sigtap', 'like', "%{$search}%")
                    ->orWhere('descricao_sigtap', 'like', "%{$search}%");
            });
        }

        $registros = $query->orderBy('competencia', 'desc')
            ->orderBy('unidade')
            ->orderBy('codigo_sigtap')
            ->paginate(30)
            ->withQueryString();

        $competencias = SEsus::query()
            ->select('competencia')
            ->distinct()
            ->orderByDesc('competencia')
            ->pluck('competencia');

        return view('esus.index', compact('registros', 'competencias'));
    }

    public function edit(SEsus $esu)
    {
        return view('esus.edit', ['registro' => $esu]);
    }

    public function update(SEsusRequest $request, SEsus $esu)
    {
        $esu->update($request->validated());

        return redirect()->route('esus.index', ['competencia' => $esu->competencia])
            ->with('success', 'Registro atualizado com sucesso!');
    }

    public function destroy(SEsus $esu)
    {
        $competencia = $esu->competencia;
        $esu->delete();

        return redirect()->route('esus.index', ['competencia' => $competencia])
            ->with('success', 'Registro excluído com sucesso!');
    }
}
