<?php

namespace App\Http\Controllers;

use App\Models\SApa;
use App\Models\Prestador;
use Illuminate\Http\Request;

class SApaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SApa::with(['prestador']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('APA_NUM', 'like', "%{$search}%")
                  ->orWhere('APA_NMPCN', 'like', "%{$search}%")
                  ->orWhere('APA_PRIPAL', 'like', "%{$search}%")
                  ->orWhere('APA_UID', 'like', "%{$search}%")
                  ->orWhereHas('prestador', function ($q) use ($search) {
                      $q->where('re_cnome', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by prestador
        if ($request->filled('prestador')) {
            $query->where('APA_UID', $request->prestador);
        }

        // Filter by competencia
        if ($request->filled('competencia')) {
            $query->where('APA_MVM', $request->competencia);
        }

        // Filter by OCI (procedimentos que começam com '09')
        if ($request->filled('oci') && $request->oci === '1') {
            $query->oci();
        }

        // Filter by patient name
        if ($request->filled('paciente')) {
            $query->byPatientName($request->paciente);
        }

        // Filter by active APACs
        if ($request->filled('ativo') && $request->ativo === '1') {
            $query->active();
        }

        $sApas = $query->orderBy('APA_MVM', 'desc')
                      ->orderBy('APA_NUM', 'desc')
                      ->paginate(20)
                      ->withQueryString();

        // Get filter options
        $prestadores = Prestador::active()->orderBy('re_cnome')->get();
        $competencias = SApa::select('APA_MVM')
                           ->distinct()
                           ->orderBy('APA_MVM', 'desc')
                           ->limit(12)
                           ->get();

        return view('sapa.index', compact('sApas', 'prestadores', 'competencias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $prestadores = Prestador::active()->orderBy('re_cnome')->get();
        return view('sapa.create', compact('prestadores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'APA_UID' => 'required|string|max:7|exists:prestador,re_cunid',
            'APA_NUM' => 'required|string|max:13|unique:s_apa,APA_NUM',
            'APA_EMISSA' => 'nullable|string|max:8',
            'APA_DTINIC' => 'nullable|string|max:8',
            'APA_DTFIM' => 'nullable|string|max:8',
            'APA_TPATEN' => 'nullable|string|max:2',
            'APA_TPAPAC' => 'nullable|string|max:1',
            'APA_NMPCN' => 'nullable|string|max:30',
            'APA_UFPCN' => 'nullable|string|max:3',
            'APA_MAEPCN' => 'nullable|string|max:30',
            'APA_LOGPCN' => 'nullable|string|max:30',
            'APA_NUMPCN' => 'nullable|string|max:5',
            'APA_CPLPCN' => 'nullable|string|max:10',
            'APA_CEPPCN' => 'nullable|string|max:8',
            'APA_MUNPCN' => 'nullable|string|max:7',
            'APA_DTNASC' => 'nullable|string|max:8',
            'APA_SEXPCN' => 'nullable|string|max:1|in:M,F',
            'APA_PRIPAL' => 'nullable|string|max:9',
            'APA_CMP' => 'nullable|string|max:6',
            'APA_MVM' => 'nullable|string|max:6',
            'APA_EMAIL' => 'nullable|email|max:40',
            'APA_TEL' => 'nullable|string|max:9',
            'APA_DDD' => 'nullable|string|max:2',
        ]);

        try {
            SApa::create($request->all());
            
            return redirect()->route('sapa.index')
                           ->with('success', 'APAC criada com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Erro ao criar APAC: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SApa $sApa)
    {
        $sApa->load(['prestador', 'sPaps.procedimento', 'sPaps.cbo']);
        return view('sapa.show', compact('sApa'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SApa $sApa)
    {
        $prestadores = Prestador::active()->orderBy('re_cnome')->get();
        return view('sapa.edit', compact('sApa', 'prestadores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SApa $sApa)
    {
        $request->validate([
            'APA_UID' => 'required|string|max:7|exists:prestador,re_cunid',
            'APA_NUM' => 'required|string|max:13|unique:s_apa,APA_NUM,' . $sApa->APA_NUM . ',APA_NUM',
            'APA_EMISSA' => 'nullable|string|max:8',
            'APA_DTINIC' => 'nullable|string|max:8',
            'APA_DTFIM' => 'nullable|string|max:8',
            'APA_TPATEN' => 'nullable|string|max:2',
            'APA_TPAPAC' => 'nullable|string|max:1',
            'APA_NMPCN' => 'nullable|string|max:30',
            'APA_UFPCN' => 'nullable|string|max:3',
            'APA_MAEPCN' => 'nullable|string|max:30',
            'APA_LOGPCN' => 'nullable|string|max:30',
            'APA_NUMPCN' => 'nullable|string|max:5',
            'APA_CPLPCN' => 'nullable|string|max:10',
            'APA_CEPPCN' => 'nullable|string|max:8',
            'APA_MUNPCN' => 'nullable|string|max:7',
            'APA_DTNASC' => 'nullable|string|max:8',
            'APA_SEXPCN' => 'nullable|string|max:1|in:M,F',
            'APA_PRIPAL' => 'nullable|string|max:9',
            'APA_CMP' => 'nullable|string|max:6',
            'APA_MVM' => 'nullable|string|max:6',
            'APA_EMAIL' => 'nullable|email|max:40',
            'APA_TEL' => 'nullable|string|max:9',
            'APA_DDD' => 'nullable|string|max:2',
        ]);

        try {
            $sApa->update($request->all());
            
            return redirect()->route('sapa.index')
                           ->with('success', 'APAC atualizada com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', 'Erro ao atualizar APAC: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SApa $sApa)
    {
        try {
            // Check if APAC has related production records
            if ($sApa->sPaps()->count() > 0) {
                return back()->with('error', 'Não é possível excluir esta APAC pois possui registros de produção relacionados.');
            }

            $sApa->delete();
            
            return redirect()->route('sapa.index')
                           ->with('success', 'APAC excluída com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir APAC: ' . $e->getMessage());
        }
    }

    /**
     * Get statistics for dashboard.
     */
    public function getStatistics()
    {
        $stats = [
            'total_apacs' => SApa::count(),
            'total_oci' => SApa::oci()->count(),
            'total_active' => SApa::active()->count(),
            'by_competencia' => SApa::select('APA_MVM', \DB::raw('COUNT(*) as count'))
                                  ->groupBy('APA_MVM')
                                  ->orderBy('APA_MVM', 'desc')
                                  ->limit(6)
                                  ->get(),
            'by_prestador' => SApa::select('APA_UID', \DB::raw('COUNT(*) as count'))
                                 ->with('prestador')
                                 ->groupBy('APA_UID')
                                 ->orderBy('count', 'desc')
                                 ->limit(10)
                                 ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Export data to CSV.
     */
    public function export(Request $request)
    {
        $query = SApa::with(['prestador']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('APA_NUM', 'like', "%{$search}%")
                  ->orWhere('APA_NMPCN', 'like', "%{$search}%")
                  ->orWhere('APA_PRIPAL', 'like', "%{$search}%");
            });
        }

        if ($request->filled('prestador')) {
            $query->where('APA_UID', $request->prestador);
        }

        if ($request->filled('competencia')) {
            $query->where('APA_MVM', $request->competencia);
        }

        if ($request->filled('oci') && $request->oci === '1') {
            $query->oci();
        }

        $data = $query->orderBy('APA_MVM', 'desc')
                     ->orderBy('APA_NUM', 'desc')
                     ->get();

        // Create CSV export
        $filename = 'apacs_' . date('Y-m-d_H-i-s') . '.csv';
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
                'Paciente',
                'Data Nascimento',
                'Sexo',
                'Procedimento Principal',
                'Competência',
                'Data Início',
                'Data Fim',
                'Tipo Atendimento',
                'Endereço',
                'Telefone',
                'Email'
            ], ';');
            
            // Data
            foreach ($data as $record) {
                fputcsv($file, [
                    $record->APA_NUM,
                    $record->prestador->re_cnome ?? '',
                    $record->APA_NMPCN,
                    $record->formatted_birth_date,
                    $record->patient_gender_description,
                    $record->APA_PRIPAL,
                    $record->formatted_competencia,
                    $record->formatted_start_date,
                    $record->formatted_end_date,
                    $record->APA_TPATEN,
                    $record->APA_LOGPCN . ', ' . $record->APA_NUMPCN . ' - ' . $record->APA_BAIRRO,
                    '(' . $record->APA_DDD . ') ' . $record->APA_TEL,
                    $record->APA_EMAIL
                ], ';');
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
