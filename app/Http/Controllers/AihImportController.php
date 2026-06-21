<?php

namespace App\Http\Controllers;

use App\Services\AihTextImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class AihImportController extends Controller
{
    private const SESSION_KEY = 'aih_import_preview';

    public function create()
    {
        return view('aih.import');
    }

    public function store(Request $request, AihTextImportService $service)
    {
        $request->validate([
            'aih_file' => ['required', 'file', 'max:20480'],
            'hpa_file' => ['required', 'file', 'max:20480'],
        ], [
            'aih_file.required' => 'Selecione o arquivo de Resumo AIH.',
            'hpa_file.required' => 'Selecione o arquivo de Procedimentos AIH.',
            'aih_file.max'      => 'O arquivo de AIH não pode ter mais de 20 MB.',
            'hpa_file.max'      => 'O arquivo de HPA não pode ter mais de 20 MB.',
        ]);

        foreach (['aih_file' => 'txt', 'hpa_file' => 'txt'] as $input => $expected) {
            $ext = strtolower($request->file($input)->getClientOriginalExtension());
            if ($ext !== $expected) {
                return back()->with('error', "O arquivo {$input} deve ter extensão .txt.");
            }
        }

        $timestamp = now()->format('Ymd_His');

        $aihPath = $request->file('aih_file')
            ->storeAs('imports/aih', "aih_{$timestamp}.txt");

        $hpaPath = $request->file('hpa_file')
            ->storeAs('imports/aih', "hpa_{$timestamp}.txt");

        try {
            $aihRecords = $service->parseAihFile(Storage::disk('local')->path($aihPath));
            $hpaRecords = $service->parseHpaFile(Storage::disk('local')->path($hpaPath));

            if (empty($aihRecords)) {
                return back()->with('error', 'O arquivo de Resumo AIH não contém registros válidos.');
            }

            $competencias = $service->detectCompetencias($aihRecords);
            $competencias = $service->checkExisting($competencias);
            $competencias = $service->enrichWithHpa($competencias, $hpaRecords);

            Session::put(self::SESSION_KEY, [
                'aih_path'    => $aihPath,
                'hpa_path'    => $hpaPath,
                'competencias'=> $competencias,
                'total_aih'   => count($aihRecords),
                'total_hpa'   => count($hpaRecords),
            ]);

            return redirect()->route('aih.import.preview')
                ->with('success', sprintf(
                    'Arquivos lidos: %d AIH, %d procedimentos em %d combinação(ões) CNES/Competência.',
                    count($aihRecords),
                    count($hpaRecords),
                    count($competencias)
                ));
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao processar os arquivos: ' . $e->getMessage());
        }
    }

    public function preview()
    {
        $preview = Session::get(self::SESSION_KEY);

        if ($preview === null) {
            return redirect()->route('aih.import')
                ->with('error', 'Nenhuma importação em andamento. Envie os arquivos novamente.');
        }

        return view('aih.import-preview', compact('preview'));
    }

    public function apply(Request $request, AihTextImportService $service)
    {
        $preview = Session::get(self::SESSION_KEY);

        if ($preview === null) {
            return redirect()->route('aih.import')
                ->with('error', 'Sessão de importação expirada. Envie os arquivos novamente.');
        }

        $replace = $request->input('replace', '0') === '1';

        try {
            $aihRecords = $service->parseAihFile(Storage::disk('local')->path($preview['aih_path']));
            $hpaRecords = $service->parseHpaFile(Storage::disk('local')->path($preview['hpa_path']));

            $result = $service->applyImport($aihRecords, $hpaRecords, $replace);

            Session::forget(self::SESSION_KEY);

            $msg = "Importação concluída: {$result['inserted_aih']} AIH e {$result['inserted_hpa']} procedimentos gravados.";

            if (!empty($result['replaced'])) {
                $msg .= ' Substituídos: ' . implode(', ', $result['replaced']) . '.';
            }

            if (!empty($result['skipped'])) {
                $msg .= ' Ignorados (já existiam): ' . implode(', ', $result['skipped']) . '.';
            }

            return redirect()->route('aih.import')
                ->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao aplicar importação: ' . $e->getMessage());
        }
    }
}
