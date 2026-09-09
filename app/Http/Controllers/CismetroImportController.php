<?php

namespace App\Http\Controllers;

use App\Services\CismetroImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CismetroImportController extends Controller
{
    private const SESSION_KEY = 'cismetro_import_result';

    public function create(): View
    {
        return view('cismetro.import');
    }

    public function store(Request $request, CismetroImportService $service): RedirectResponse
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:xlsx,pdf', 'max:51200'],
            'competencia_inicial' => ['required', 'regex:/^\d{6}$/'],
            'competencia_final' => ['nullable', 'regex:/^\d{6}$/'],
            'credenciamento' => ['required', 'string', 'max:40'],
        ], [
            'arquivo.required' => 'Selecione o arquivo XLSX ou PDF da tabela CISMETRO.',
            'arquivo.mimes' => 'O arquivo deve estar no formato .xlsx ou .pdf.',
            'competencia_inicial.regex' => 'Competência inicial inválida. Use o formato AAAAMM (ex: 202408).',
            'competencia_final.regex' => 'Competência final inválida. Use o formato AAAAMM (ex: 999999).',
        ]);

        $competenciaInicial = $request->input('competencia_inicial');
        $competenciaFinal = $request->input('competencia_final') ?: '999999';

        if ($competenciaFinal < $competenciaInicial) {
            return back()->withInput()->with('error', 'A competência final não pode ser anterior à competência inicial.');
        }

        $file = $request->file('arquivo');
        $extension = strtolower($file->getClientOriginalExtension());
        $storedPath = $file->storeAs('imports/cismetro', 'cismetro_'.now()->format('Ymd_His').'.'.$extension);
        $absolutePath = Storage::disk('local')->path($storedPath);

        try {
            $parsed = $service->parseFile($absolutePath, $extension);
            $result = $service->buildResult(
                $parsed['rows'],
                $parsed['skipped'],
                $competenciaInicial,
                $competenciaFinal,
                $request->input('credenciamento'),
            );
            $result['source'] = $extension;

            Session::put(self::SESSION_KEY, $result);

            return redirect()->route('cismetro.import.preview');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Erro ao processar o arquivo: '.$e->getMessage());
        } finally {
            Storage::disk('local')->delete($storedPath);
        }
    }

    public function preview(): View|RedirectResponse
    {
        $result = Session::get(self::SESSION_KEY);

        if ($result === null) {
            return redirect()->route('cismetro.import')
                ->with('error', 'Nenhuma importação recente. Envie o arquivo novamente.');
        }

        return view('cismetro.import-preview', compact('result'));
    }

    public function apply(Request $request, CismetroImportService $service): RedirectResponse
    {
        $result = Session::get(self::SESSION_KEY);

        if ($result === null) {
            return redirect()->route('cismetro.import')
                ->with('error', 'Sessão de importação expirada. Envie o arquivo novamente.');
        }

        $request->validate([
            'selected' => ['nullable', 'array'],
            'selected.*' => ['string'],
        ]);

        $selected = $request->input('selected', []);

        if ($result['created'] === [] && $selected === []) {
            return back()->with('error', 'Nada a aplicar: selecione ao menos uma alteração.');
        }

        $applied = $service->applyImport($selected, $result);

        Session::forget(self::SESSION_KEY);

        return redirect()->route('cismetro.index')
            ->with('success', "Importação concluída: {$applied['created']} novo(s), {$applied['updated']} atualizado(s).");
    }
}
