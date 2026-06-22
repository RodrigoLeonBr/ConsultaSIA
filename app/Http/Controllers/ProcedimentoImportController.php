<?php

namespace App\Http\Controllers;

use App\Services\ProcedimentoDbfImportService;
use App\Services\ProcedimentoTuImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class ProcedimentoImportController extends Controller
{
    private const SESSION_KEY    = 'procedimento_import_result';
    private const SESSION_KEY_TU = 'procedimento_tu_import_result';

    // ─── DBF (SIA) ────────────────────────────────────────────────────────────

    public function create()
    {
        return view('procedimento.import');
    }

    public function store(Request $request, ProcedimentoDbfImportService $importService)
    {
        $request->validate([
            'dbf_pa_file'  => ['required', 'file', 'max:10240'],
            'dbf_rub_file' => ['required', 'file', 'max:5120'],
        ], [
            'dbf_pa_file.required'  => 'Selecione o arquivo S_PA.DBF.',
            'dbf_rub_file.required' => 'Selecione o arquivo S_RUB.DBF.',
            'dbf_pa_file.max'       => 'S_PA.DBF não pode ter mais de 10 MB.',
            'dbf_rub_file.max'      => 'S_RUB.DBF não pode ter mais de 5 MB.',
        ]);

        $paFile  = $request->file('dbf_pa_file');
        $rubFile = $request->file('dbf_rub_file');

        if (strtolower($paFile->getClientOriginalExtension()) !== 'dbf') {
            return back()->with('error', 'S_PA.DBF deve ter extensão .DBF.');
        }

        if (strtolower($rubFile->getClientOriginalExtension()) !== 'dbf') {
            return back()->with('error', 'S_RUB.DBF deve ter extensão .DBF.');
        }

        $timestamp     = now()->format('Ymd_His');
        $paStoredPath  = $paFile->storeAs('imports/procedimento', "S_PA_{$timestamp}.DBF");
        $rubStoredPath = $rubFile->storeAs('imports/procedimento', "S_RUB_{$timestamp}.DBF");

        try {
            $paPath  = Storage::disk('local')->path($paStoredPath);
            $rubPath = Storage::disk('local')->path($rubStoredPath);
            $result  = $importService->import($paPath, $rubPath, autoCreate: true);

            Session::put(self::SESSION_KEY, $result);

            return redirect()
                ->route('procedimento.import.preview')
                ->with('success', sprintf(
                    'Importação concluída (competência %s): %d procedimento(s) novo(s), %d alteração(ões) pendente(s). Tabela s_rub: %d criada(s), %d atualizada(s).',
                    $result['competence'],
                    count($result['created']),
                    count($result['changed']),
                    count($result['s_rub']['created']),
                    count($result['s_rub']['updated'])
                ));
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao ler os arquivos DBF: ' . $e->getMessage());
        }
    }

    public function preview()
    {
        $result = Session::get(self::SESSION_KEY);

        if ($result === null) {
            return redirect()
                ->route('procedimento.import')
                ->with('error', 'Nenhuma importação recente. Envie os arquivos S_PA.DBF e S_RUB.DBF novamente.');
        }

        return view('procedimento.import-preview', compact('result'));
    }

    public function apply(Request $request, ProcedimentoDbfImportService $importService)
    {
        $result = Session::get(self::SESSION_KEY);

        if ($result === null) {
            return redirect()
                ->route('procedimento.import')
                ->with('error', 'Sessão de importação expirada. Envie os arquivos novamente.');
        }

        $request->validate([
            'selected'   => ['nullable', 'array'],
            'selected.*' => ['string', 'max:10'],
        ]);

        $selected = $request->input('selected', []);

        if ($selected === []) {
            return back()->with('error', 'Selecione ao menos um procedimento para atualizar.');
        }

        $applied = $importService->applyChanges($selected, $result['changed']);

        $remainingChanged = collect($result['changed'])
            ->reject(fn ($item) => in_array($item['codigo'], $selected, true))
            ->values()
            ->all();

        $result['changed'] = $remainingChanged;
        Session::put(self::SESSION_KEY, $result);

        return redirect()
            ->route('procedimento.import.preview')
            ->with('success', "{$applied} procedimento(s) atualizado(s) com sucesso.");
    }

    // ─── TU (SIH/AIH) ─────────────────────────────────────────────────────────

    public function storeTu(Request $request, ProcedimentoTuImportService $importService)
    {
        $request->validate([
            'tu_file' => ['required', 'file', 'max:20480', 'mimes:txt,csv'],
        ], [
            'tu_file.required' => 'Selecione o arquivo TU_PROCEDIMENTO.TXT.',
            'tu_file.max'      => 'O arquivo TU não pode ter mais de 20 MB.',
            'tu_file.mimes'    => 'O arquivo deve ter extensão .TXT ou .CSV.',
        ]);

        $tuFile = $request->file('tu_file');
        $ext    = strtolower($tuFile->getClientOriginalExtension());

        if (! in_array($ext, ['txt', 'csv'], true)) {
            return back()->with('error', 'O arquivo TU deve ter extensão .TXT.');
        }

        $timestamp    = now()->format('Ymd_His');
        $storedPath   = $tuFile->storeAs('imports/procedimento', "TU_{$timestamp}.TXT");
        $absolutePath = Storage::disk('local')->path($storedPath);

        try {
            $result = $importService->import($absolutePath);

            Session::put(self::SESSION_KEY_TU, $result);

            return redirect()
                ->route('procedimento.import.tu.preview')
                ->with('success', sprintf(
                    'TU importado: %d procedimento(s) novo(s) gravado(s), %d alteração(ões) pendente(s), %d inalterado(s).',
                    count($result['created']),
                    count($result['changed']),
                    $result['unchanged']
                ));
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao ler o arquivo TU: ' . $e->getMessage());
        }
    }

    public function previewTu()
    {
        $result = Session::get(self::SESSION_KEY_TU);

        if ($result === null) {
            return redirect()
                ->route('procedimento.import')
                ->with('error', 'Nenhuma importação TU recente. Envie o arquivo novamente.');
        }

        return view('procedimento.import-tu-preview', compact('result'));
    }

    public function applyTu(Request $request, ProcedimentoTuImportService $importService)
    {
        $result = Session::get(self::SESSION_KEY_TU);

        if ($result === null) {
            return redirect()
                ->route('procedimento.import')
                ->with('error', 'Sessão TU expirada. Envie o arquivo novamente.');
        }

        $request->validate([
            'selected'   => ['nullable', 'array'],
            'selected.*' => ['string', 'max:10'],
        ]);

        $selected = $request->input('selected', []);

        if ($selected === []) {
            return back()->with('error', 'Selecione ao menos um procedimento para atualizar.');
        }

        $applied = $importService->applyChanges($selected, $result['changed']);

        $remainingChanged = collect($result['changed'])
            ->reject(fn ($item) => in_array($item['codigo'], $selected, true))
            ->values()
            ->all();

        $result['changed'] = $remainingChanged;
        Session::put(self::SESSION_KEY_TU, $result);

        return redirect()
            ->route('procedimento.import.tu.preview')
            ->with('success', "{$applied} procedimento(s) atualizado(s) com sucesso.");
    }
}
