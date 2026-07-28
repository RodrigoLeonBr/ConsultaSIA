<?php

namespace App\Http\Controllers;

use App\Services\EsusImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class EsusImportController extends Controller
{
    public const SESSION_KEY = 'esus_import_preview';

    public function create(EsusImportService $service)
    {
        $competencias = [];
        $apiError = null;

        try {
            $competencias = $service->getCompetencias();
        } catch (\Throwable $e) {
            $apiError = $e->getMessage();
        }

        $history = $service->history();

        return view('esus.import', compact('competencias', 'history', 'apiError'));
    }

    public function store(Request $request, EsusImportService $service)
    {
        $request->validate([
            'competencia' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ], [
            'competencia.required' => 'Selecione a competência.',
            'competencia.regex' => 'Competência deve estar no formato AAAA-MM.',
        ]);

        try {
            $preview = $service->preview($request->input('competencia'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao consultar a API e-SUS: '.$e->getMessage());
        }

        Session::put(self::SESSION_KEY, $preview);

        return redirect()->route('esus.import.preview');
    }

    public function preview()
    {
        $preview = Session::get(self::SESSION_KEY);

        if ($preview === null) {
            return redirect()->route('esus.import')
                ->with('error', 'Nenhuma importação em andamento. Selecione a competência novamente.');
        }

        return view('esus.import-preview', compact('preview'));
    }

    public function apply(Request $request, EsusImportService $service)
    {
        $preview = Session::get(self::SESSION_KEY);

        if ($preview === null) {
            return redirect()->route('esus.import')
                ->with('error', 'Sessão de importação expirada. Selecione a competência novamente.');
        }

        if (($preview['existentes'] ?? 0) > 0 && ! $request->boolean('confirm_replace')) {
            return back()->with('error', "A competência {$preview['competencia']} já foi importada ({$preview['existentes']} linhas). Marque a confirmação para apagar a importação anterior e importar novamente.");
        }

        try {
            $result = $service->apply($preview['competencia']);
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao aplicar importação: '.$e->getMessage());
        }

        Session::forget(self::SESSION_KEY);

        $msg = "Importação da competência {$preview['competencia']} concluída: {$result['inserted']} linhas gravadas";
        if ($result['replaced'] > 0) {
            $msg .= " ({$result['replaced']} substituídas)";
        }
        $msg .= '.';

        return redirect()->route('esus.import')->with('success', $msg);
    }
}
