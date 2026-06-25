<?php

namespace App\Http\Controllers;

use App\Services\SusPaulistaXlsxImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SusPaulistaImportController extends Controller
{
    private const SESSION_KEY = 'sus_paulista_import_result';

    private const SESSION_JOB_KEY = 'sus_paulista_import_job';

    public function create()
    {
        return view('sus-paulista.import');
    }

    public function store(Request $request, SusPaulistaXlsxImportService $importService)
    {
        $request->validate([
            'modalidade' => ['required', 'in:sia,sih'],
            'xlsx_file' => ['required', 'file', 'mimes:xlsx', 'max:51200'],
            'competencia_inicial' => ['required', 'regex:/^\d{6}$/'],
            'competencia_final' => ['nullable', 'regex:/^\d{6}$/'],
        ], [
            'modalidade.required' => 'Selecione a modalidade (SIA ou SIH).',
            'xlsx_file.required' => 'Selecione o arquivo XLSX da tabela SUS Paulista.',
            'xlsx_file.mimes' => 'O arquivo deve estar no formato .xlsx.',
            'competencia_inicial.regex' => 'Competência inicial inválida. Use o formato AAAAMM (ex: 202602).',
            'competencia_final.regex' => 'Competência final inválida. Use o formato AAAAMM (ex: 999999).',
        ]);

        $competenciaInicial = $request->input('competencia_inicial');
        $competenciaFinal = $request->input('competencia_final', '999999');

        if ($competenciaFinal < $competenciaInicial) {
            return back()->with('error', 'A competência final não pode ser anterior à competência inicial.');
        }

        $modalidade = $request->input('modalidade');
        $timestamp = now()->format('Ymd_His');
        $storedPath = $request->file('xlsx_file')
            ->storeAs('imports/sus-paulista', "sus_paulista_{$modalidade}_{$timestamp}.xlsx");

        $absolutePath = Storage::disk('local')->path($storedPath);

        try {
            $prepared = $importService->prepareImport($absolutePath);

            $job = $importService->createEmptyJob(
                $absolutePath,
                $modalidade,
                $prepared['column_map'],
                $prepared['total_rows'],
                $competenciaInicial,
                $competenciaFinal,
            );

            $job['stored_path'] = $storedPath;

            Session::put(self::SESSION_JOB_KEY, $job);
            Session::forget(self::SESSION_KEY);

            return redirect()->route('sus-paulista.import.process');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao preparar o arquivo XLSX: ' . $e->getMessage());
        }
    }

    public function process(): View|\Illuminate\Http\RedirectResponse
    {
        $job = Session::get(self::SESSION_JOB_KEY);

        if ($job === null) {
            return redirect()
                ->route('sus-paulista.import')
                ->with('error', 'Nenhuma importação em andamento. Envie o arquivo novamente.');
        }

        $totalRows = (int) $job['total_rows'];
        $chunkSize = (int) ($job['chunk_row_size'] ?? SusPaulistaXlsxImportService::resolveChunkRowSize($totalRows));
        $estimatedChunks = (int) max(1, ceil(max(0, $totalRows - 1) / $chunkSize));

        return view('sus-paulista.import-processing', [
            'job' => $job,
            'chunkSize' => $chunkSize,
            'estimatedChunks' => $estimatedChunks,
        ]);
    }

    public function processChunk(Request $request, SusPaulistaXlsxImportService $importService): JsonResponse
    {
        $job = Session::get(self::SESSION_JOB_KEY);

        if ($job === null) {
            return response()->json([
                'error' => 'Sessão de importação expirada. Envie o arquivo novamente.',
            ], 422);
        }

        if (! is_file($job['file_path'])) {
            Session::forget(self::SESSION_JOB_KEY);

            return response()->json([
                'error' => 'Arquivo temporário não encontrado. Envie o arquivo novamente.',
            ], 422);
        }

        set_time_limit(120);

        try {
            $outcome = $importService->processChunk($job);
            Session::put(self::SESSION_JOB_KEY, $job);

            if ($outcome['done']) {
                Session::put(self::SESSION_KEY, $outcome['result']);
                Session::forget(self::SESSION_JOB_KEY);

                $result = $outcome['result'];

                return response()->json([
                    'done' => true,
                    'progress' => $outcome['progress'],
                    'chunk' => $outcome['chunk'],
                    'summary' => [
                        'created_count' => $result['created_count'] ?? 0,
                        'changed_count' => count($result['changed'] ?? []),
                        'unchanged' => $result['unchanged'] ?? 0,
                        'total_xlsx' => $result['total_xlsx'] ?? 0,
                    ],
                    'redirect' => route('sus-paulista.import.preview'),
                ]);
            }

            return response()->json([
                'done' => false,
                'progress' => $outcome['progress'],
                'chunk' => $outcome['chunk'],
                'totals' => [
                    'created_count' => $job['result']['created_count'] ?? 0,
                    'changed_count' => count($job['result']['changed'] ?? []),
                    'unchanged' => $job['result']['unchanged'] ?? 0,
                    'valid_rows' => $job['result']['total_xlsx'] ?? 0,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Erro ao processar bloco: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function preview()
    {
        $result = Session::get(self::SESSION_KEY);

        if ($result === null) {
            return redirect()
                ->route('sus-paulista.import')
                ->with('error', 'Nenhuma importação recente. Envie o arquivo novamente.');
        }

        return view('sus-paulista.import-preview', compact('result'));
    }

    public function apply(Request $request, SusPaulistaXlsxImportService $importService)
    {
        $result = Session::get(self::SESSION_KEY);

        if ($result === null) {
            return redirect()
                ->route('sus-paulista.import')
                ->with('error', 'Sessão de importação expirada. Envie o arquivo novamente.');
        }

        $request->validate([
            'selected' => ['nullable', 'array'],
            'selected.*' => ['string', 'max:11'],
        ]);

        $selected = $request->input('selected', []);

        if ($selected === []) {
            return back()->with('error', 'Selecione ao menos um procedimento para atualizar.');
        }

        $applied = $importService->applyChanges($selected, $result['changed'], [
            'modalidade' => $result['modalidade'],
            'competencia_inicial' => $result['competencia_inicial'],
            'competencia_final' => $result['competencia_final'],
        ]);

        $remainingChanged = collect($result['changed'])
            ->reject(fn ($item) => in_array($item['codigo'], $selected, true))
            ->values()
            ->all();

        $result['changed'] = $remainingChanged;
        Session::put(self::SESSION_KEY, $result);

        return redirect()
            ->route('sus-paulista.import.preview')
            ->with('success', "{$applied} procedimento(s) atualizado(s) com sucesso.");
    }
}
