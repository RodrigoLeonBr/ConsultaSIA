<?php

namespace App\Http\Controllers;

use App\Models\SPap;
use App\Models\Prestador;
use App\Models\Procedimento;
use App\Models\Cbo;
use App\Models\SApa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SPapController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SPap::with(['prestador', 'procedimento', 'cbo', 'sApa']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('PAP_NUM', 'like', "%{$search}%")
                  ->orWhere('PAP_UID', 'like', "%{$search}%")
                  ->orWhere('PAP_PA', 'like', "%{$search}%")
                  ->orWhere('PAP_CIDPRI', 'like', "%{$search}%")
                  ->orWhereHas('prestador', function ($q) use ($search) {
                      $q->where('re_cnome', 'like', "%{$search}%");
                  })
                  ->orWhereHas('procedimento', function ($q) use ($search) {
                      $q->where('procedimento', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by prestador
        if ($request->filled('prestador')) {
            $query->where('PAP_UID', $request->prestador);
        }

        // Filter by competencia
        if ($request->filled('competencia')) {
            $query->where('PAP_MVM', $request->competencia);
        }

        // Filter by procedimento
        if ($request->filled('procedimento')) {
            $query->where('PAP_PA', $request->procedimento);
        }

        // Filter by OCI (procedimentos que começam com '09')
        if ($request->filled('oci') && $request->oci === '1') {
            $query->oci();
        }

        // Filter by production quantity
        if ($request->filled('com_producao') && $request->com_producao === '1') {
            $query->withProduction();
        }

        $sPaps = $query->orderBy('PAP_MVM', 'desc')
                      ->orderBy('PAP_NUM', 'desc')
                      ->paginate(20)
                      ->withQueryString();

        // Get filter options
        $prestadores = Prestador::active()->orderBy('re_cnome')->get();
        $procedimentos = Procedimento::orderBy('procedimento')->get();
        $competencias = SPap::select('PAP_MVM')
                           ->distinct()
                           ->orderBy('PAP_MVM', 'desc')
                           ->limit(12)
                           ->get();

        return view('spap.index', compact('sPaps', 'prestadores', 'procedimentos', 'competencias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $prestadores = Prestador::active()->orderBy('re_cnome')->get();
        $procedimentos = Procedimento::orderBy('procedimento')->get();
        $cbos = Cbo::orderBy('ds_cbo')->get();
        $sApas = SApa::orderBy('APA_NUM')->get();

        return view('spap.create', compact('prestadores', 'procedimentos', 'cbos', 'sApas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'PAP_UID' => 'required|string|max:7|exists:prestador,re_cunid',
            'PAP_CMP' => 'nullable|string|max:6',
            'PAP_NUM' => 'required|string|max:13',
            'PAP_PA' => 'required|string|max:10|exists:procedimento,codigo',
            'PAP_SEQ' => 'nullable|string|max:2',
            'PAP_CBO' => 'nullable|string|max:6|exists:cbo,cbo',
            'PAP_IDADE' => 'nullable|integer|min:0|max:150',
            'PAP_QT_P' => 'nullable|numeric|min:0',
            'PAP_QT_A' => 'nullable|numeric|min:0',
            'PAP_MVM' => 'nullable|string|max:6',
            'PAP_ORG' => 'nullable|string|max:3',
            'PAP_CIDPRI' => 'nullable|string|max:6',
            'PAP_CIDSEC' => 'nullable|string|max:6',
            'PAP_VL_FED' => 'nullable|numeric|min:0',
            'PAP_VL_LOC' => 'nullable|numeric|min:0',
            'PAP_VL_INC' => 'nullable|numeric|min:0',
            'PAP_RUB' => 'nullable|string|max:6',
            'PAP_TPFIN' => 'nullable|string|max:1',
        ]);

        try {
            SPap::create($request->all());
            
            return redirect()->route('spap.index')
                           ->with('success', 'Registro de APAC criado com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Erro ao criar registro de APAC: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SPap $sPap)
    {
        $sPap->load(['prestador', 'procedimento', 'cbo', 'sApa']);
        return view('spap.show', compact('sPap'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SPap $sPap)
    {
        $prestadores = Prestador::active()->orderBy('re_cnome')->get();
        $procedimentos = Procedimento::orderBy('procedimento')->get();
        $cbos = Cbo::orderBy('ds_cbo')->get();
        $sApas = SApa::orderBy('APA_NUM')->get();

        return view('spap.edit', compact('sPap', 'prestadores', 'procedimentos', 'cbos', 'sApas'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SPap $sPap)
    {
        $request->validate([
            'PAP_UID' => 'required|string|max:7|exists:prestador,re_cunid',
            'PAP_CMP' => 'nullable|string|max:6',
            'PAP_NUM' => 'required|string|max:13',
            'PAP_PA' => 'required|string|max:10|exists:procedimento,codigo',
            'PAP_SEQ' => 'nullable|string|max:2',
            'PAP_CBO' => 'nullable|string|max:6|exists:cbo,cbo',
            'PAP_IDADE' => 'nullable|integer|min:0|max:150',
            'PAP_QT_P' => 'nullable|numeric|min:0',
            'PAP_QT_A' => 'nullable|numeric|min:0',
            'PAP_MVM' => 'nullable|string|max:6',
            'PAP_ORG' => 'nullable|string|max:3',
            'PAP_CIDPRI' => 'nullable|string|max:6',
            'PAP_CIDSEC' => 'nullable|string|max:6',
            'PAP_VL_FED' => 'nullable|numeric|min:0',
            'PAP_VL_LOC' => 'nullable|numeric|min:0',
            'PAP_VL_INC' => 'nullable|numeric|min:0',
            'PAP_RUB' => 'nullable|string|max:6',
            'PAP_TPFIN' => 'nullable|string|max:1',
        ]);

        try {
            $sPap->update($request->all());
            
            return redirect()->route('spap.index')
                           ->with('success', 'Registro de APAC atualizado com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Erro ao atualizar registro de APAC: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SPap $sPap)
    {
        try {
            $sPap->delete();
            
            return redirect()->route('spap.index')
                           ->with('success', 'Registro de APAC excluído com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir registro de APAC: ' . $e->getMessage());
        }
    }

    /**
     * Get statistics for dashboard.
     */
    public function getStatistics()
    {
        $stats = [
            'total_records' => SPap::count(),
            'total_with_production' => SPap::withProduction()->count(),
            'total_oci' => SPap::oci()->count(),
            'total_value' => SPap::sum(DB::raw('PAP_VL_FED + PAP_VL_LOC + PAP_VL_INC')),
            'by_competencia' => SPap::select('PAP_MVM', DB::raw('COUNT(*) as count'))
                                  ->groupBy('PAP_MVM')
                                  ->orderBy('PAP_MVM', 'desc')
                                  ->limit(6)
                                  ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Export data to Excel.
     */
    public function export(Request $request)
    {
        $query = SPap::with(['prestador', 'procedimento', 'cbo', 'sApa']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('PAP_NUM', 'like', "%{$search}%")
                  ->orWhere('PAP_UID', 'like', "%{$search}%")
                  ->orWhere('PAP_PA', 'like', "%{$search}%");
            });
        }

        if ($request->filled('prestador')) {
            $query->where('PAP_UID', $request->prestador);
        }

        if ($request->filled('competencia')) {
            $query->where('PAP_MVM', $request->competencia);
        }

        if ($request->filled('oci') && $request->oci === '1') {
            $query->oci();
        }

        $data = $query->orderBy('PAP_MVM', 'desc')
                     ->orderBy('PAP_NUM', 'desc')
                     ->get();

        // Create CSV export
        $filename = 'apac_producao_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'Número APAC',
                'Prestador',
                'Competência',
                'Procedimento',
                'CBO',
                'Idade',
                'Qtd Produzida',
                'Qtd Aprovada',
                'Valor Federal',
                'Valor Local',
                'Valor Incentivo',
                'Total',
                'CID Principal',
                'CID Secundário'
            ], ';');
            
            // Data
            foreach ($data as $record) {
                fputcsv($file, [
                    $record->PAP_NUM,
                    $record->prestador->re_cnome ?? '',
                    $record->PAP_MVM,
                    $record->procedimento->procedimento ?? '',
                    $record->cbo->ds_cbo ?? '',
                    $record->PAP_IDADE,
                    $record->PAP_QT_P,
                    $record->PAP_QT_A,
                    $record->PAP_VL_FED,
                    $record->PAP_VL_LOC,
                    $record->PAP_VL_INC,
                    $record->total_value,
                    $record->PAP_CIDPRI,
                    $record->PAP_CIDSEC
                ], ';');
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
