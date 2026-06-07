<?php

namespace App\Http\Controllers;

use App\Services\PrestadorDbfImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class PrestadorImportController extends Controller
{
    private const SESSION_KEY = 'prestador_import_result';

    public function create()
    {
        return view('prestador.import');
    }

    public function store(Request $request, PrestadorDbfImportService $importService)
    {
        $request->validate([
            'dbf_file' => ['required', 'file', 'max:5120'],
        ], [
            'dbf_file.required' => 'Selecione o arquivo S_UPS.DBF.',
            'dbf_file.max' => 'O arquivo não pode ter mais de 5 MB.',
        ]);

        $file = $request->file('dbf_file');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension !== 'dbf') {
            return back()->with('error', 'O arquivo deve ter extensão .DBF.');
        }

        $filename = 'S_UPS_' . now()->format('Ymd_His') . '.DBF';
        $storedPath = $file->storeAs('imports/prestador', $filename);

        try {
            $fullPath = Storage::disk('local')->path($storedPath);
            $result = $importService->import($fullPath, autoCreate: true);

            Session::put(self::SESSION_KEY, $result);

            return redirect()
                ->route('prestador.import.preview')
                ->with('success', sprintf(
                    'Importação concluída: %d novo(s) cadastro(s) gravado(s), %d alteração(ões) pendente(s).',
                    count($result['created']),
                    count($result['changed'])
                ));
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao ler o arquivo DBF: ' . $e->getMessage());
        }
    }

    public function preview()
    {
        $result = Session::get(self::SESSION_KEY);

        if ($result === null) {
            return redirect()
                ->route('prestador.import')
                ->with('error', 'Nenhuma importação recente. Envie o arquivo S_UPS.DBF novamente.');
        }

        return view('prestador.import-preview', compact('result'));
    }

    public function apply(Request $request, PrestadorDbfImportService $importService)
    {
        $result = Session::get(self::SESSION_KEY);

        if ($result === null) {
            return redirect()
                ->route('prestador.import')
                ->with('error', 'Sessão de importação expirada. Envie o arquivo novamente.');
        }

        $request->validate([
            'selected' => ['nullable', 'array'],
            'selected.*' => ['string', 'max:7'],
        ]);

        $selected = $request->input('selected', []);

        if ($selected === []) {
            return back()->with('error', 'Selecione ao menos um prestador para atualizar.');
        }

        $applied = $importService->applyChanges($selected, $result['changed']);

        $remainingChanged = collect($result['changed'])
            ->reject(fn ($item) => in_array($item['re_cunid'], $selected, true))
            ->values()
            ->all();

        $result['changed'] = $remainingChanged;
        Session::put(self::SESSION_KEY, $result);

        return redirect()
            ->route('prestador.import.preview')
            ->with('success', "{$applied} prestador(es) atualizado(s) com sucesso.");
    }
}
