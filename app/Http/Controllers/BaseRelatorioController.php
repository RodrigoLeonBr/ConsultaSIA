<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

abstract class BaseRelatorioController extends Controller
{
    /**
     * Get available fields for report building
     */
    abstract public function getFields();

    /**
     * Get field configuration
     */
    abstract protected function getFieldConfig($field);

    /**
     * Get table name (e.g., 's_prd', 's_pap')
     */
    abstract protected function getTableName(): string;

    /**
     * Get table alias (e.g., 'sp', 'pap')
     */
    abstract protected function getTableAlias(): string;

    /**
     * Get competencia field name (e.g., 'prd_cmp', 'PAP_MVM')
     */
    abstract protected function getCompetenciaField(): string;

    /**
     * Get lookup data for a specific field
     */
    public function getLookupData(Request $request)
    {
        $field = $request->get('field');
        $search = $request->get('search', '');

        $fieldConfig = $this->getFieldConfig($field);

        if (! $fieldConfig || $fieldConfig['type'] !== 'lookup') {
            return response()->json(['error' => 'Invalid field'], 400);
        }

        $query = DB::table($fieldConfig['lookup_table'])
            ->select([
                $fieldConfig['lookup_key'].' as value',
                $fieldConfig['lookup_display'].' as label',
            ]);

        if ($search) {
            $query->where($fieldConfig['lookup_display'], 'like', '%'.$search.'%');
        }

        $data = $query->limit(50)->get();

        return response()->json($data);
    }

    /**
     * Generate report based on filters and selected fields
     */
    public function generate(Request $request)
    {
        try {
            $selectedFields = $request->get('fields', []);
            $filters = $request->get('filters', []);
            $format = $request->get('format', 'html');
            $groupBy = $request->get('group_by', true);

            // Validate that fields are selected
            if (empty($selectedFields)) {
                return response()->json([
                    'error' => 'Nenhum campo selecionado',
                ], 400);
            }

            // Build the query
            $query = $this->buildQuery($selectedFields, $filters, $groupBy);

            $sql = $query->toSql();
            $bindings = $query->getBindings();

            // Execute query
            try {
                $data = $query->get();
            } catch (\Exception $queryException) {
                \Log::error('Erro ao executar SQL do relatório: '.$queryException->getMessage(), [
                    'sql' => $sql,
                    'bindings' => $bindings,
                ]);

                return response()->json([
                    'error' => 'Erro ao gerar relatório: '.$queryException->getMessage(),
                    'sql' => $sql,
                    'bindings' => $bindings,
                ], 500);
            }

            // Format data for display
            $formattedData = $this->formatData($data, $selectedFields);

            // Calculate totals
            $totals = $this->calculateTotals($data, $selectedFields);

            switch ($format) {
                case 'excel':
                    return $this->exportExcel($formattedData, $selectedFields, $totals);
                case 'pdf':
                    return $this->exportPdf($formattedData, $selectedFields, $totals);
                case 'csv':
                    return $this->exportCsv($formattedData, $selectedFields, $totals);
                default:
                    return response()->json([
                        'data' => $formattedData,
                        'fields' => $selectedFields,
                        'total' => $data->count(),
                        'totals' => $totals,
                    ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error generating report: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Erro ao gerar relatório: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build database query based on filters and selected fields
     * Must be implemented by child classes
     */
    abstract protected function buildQuery($selectedFields, $filters, $groupBy = true);

    /**
     * Get table alias for joins
     */
    protected function getTableAliasForJoin($table)
    {
        $aliases = [
            'prestador' => 'pr',
            'cbo' => 'cb',
            'procedimento' => 'pc',
            's_rub' => 'sr',
            'cismetro' => 'cs',
        ];

        return $aliases[$table] ?? substr($table, 0, 2);
    }

    /**
     * Format data for display
     * Can be overridden by child classes for specific formatting
     */
    protected function formatData($data, $selectedFields)
    {
        return $data->map(function ($row) use ($selectedFields) {
            $formatted = [];

            foreach ($selectedFields as $field) {
                $fieldConfig = $this->getFieldConfig($field);

                if (! $fieldConfig) {
                    continue;
                }

                // Handle special field mappings - can be overridden
                $formatted = array_merge($formatted, $this->formatFieldValue($row, $field, $fieldConfig));
            }

            return $formatted;
        });
    }

    /**
     * Format a single field value
     * Can be overridden by child classes
     */
    protected function formatFieldValue($row, $field, $fieldConfig)
    {
        $formatted = [];

        if ($fieldConfig['type'] === 'lookup') {
            $displayField = $field.'_display';
            $formatted[$fieldConfig['label']] = $row->{$displayField} ?? $row->{$field} ?? '';
        } elseif ($fieldConfig['type'] === 'currency') {
            $value = $row->{$field} ?? 0;
            $formatted[$fieldConfig['label']] = 'R$ '.number_format((float) $value, 2, ',', '.');
        } elseif ($fieldConfig['type'] === 'number') {
            $value = $row->{$field} ?? 0;
            $formatted[$fieldConfig['label']] = number_format((float) $value, 0, ',', '.');
        } elseif ($fieldConfig['type'] === 'date') {
            $value = $row->{$field} ?? '';
            $formatted[$fieldConfig['label']] = $value ? date('d/m/Y', strtotime($value)) : '';
        } else {
            $formatted[$fieldConfig['label']] = $row->{$field} ?? '';
        }

        return $formatted;
    }

    /**
     * Calculate totals for numeric fields
     * Can be overridden by child classes
     */
    protected function calculateTotals($data, $selectedFields)
    {
        $totals = [];

        // This is a base implementation - child classes should override
        // to handle their specific numeric fields

        return $totals;
    }

    /**
     * Export to Excel
     */
    protected function exportExcel($data, $selectedFields, $totals = [])
    {
        try {
            $exportClass = $this->getExportClass();
            $export = new $exportClass($data, $selectedFields, $totals);

            return Excel::download($export, $this->getExportFilename('xlsx'));
        } catch (\Exception $e) {
            \Log::error('Error in Excel export: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Erro ao exportar Excel: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export to PDF
     */
    protected function exportPdf($data, $selectedFields, $totals = [])
    {
        $pdf = Pdf::loadView($this->getPdfView(), [
            'data' => $data,
            'fields' => $selectedFields,
            'totals' => $totals,
            'title' => $this->getReportTitle(),
        ]);

        return $pdf->download($this->getExportFilename('pdf'));
    }

    /**
     * Export to CSV
     */
    protected function exportCsv($data, $selectedFields, $totals = [])
    {
        $filename = $this->getExportFilename('csv');
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data, $totals) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers - get from first row if data exists
            if (! empty($data)) {
                $firstRow = $data->first();
                $fieldLabels = array_keys($firstRow);
                fputcsv($file, $fieldLabels, ';');

                // Data
                foreach ($data as $row) {
                    $csvRow = array_values($row);
                    fputcsv($file, $csvRow, ';');
                }

                // Add totals if available
                if (! empty($totals)) {
                    fputcsv($file, [], ';'); // Empty line
                    fputcsv($file, ['TOTAIS'], ';');
                    foreach ($totals as $label => $value) {
                        fputcsv($file, [$label, $value], ';');
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get export class name
     */
    abstract protected function getExportClass(): string;

    /**
     * Get PDF view name
     */
    abstract protected function getPdfView(): string;

    /**
     * Get report title
     */
    abstract protected function getReportTitle(): string;

    /**
     * Get export filename
     */
    protected function getExportFilename($extension)
    {
        return 'relatorio.'.$extension;
    }

    /**
     * Apply filter to query
     * Can be overridden by child classes for specific filter handling
     */
    protected function applyFilter($query, $filter)
    {
        $field = $filter['field'];
        $operator = $filter['operator'];
        $value = $filter['value'];

        $tableAlias = $this->getTableAlias();

        // Handle special fields - can be overridden
        if (str_starts_with($field, 'cismetro_')) {
            $field = 'cs.'.substr($field, 9); // Remove 'cismetro_' prefix
        } else {
            $field = "{$tableAlias}.{$field}";
        }

        switch ($operator) {
            case '=':
                $query->where($field, '=', $value);
                break;
            case '>':
                $query->where($field, '>', $value);
                break;
            case '<':
                $query->where($field, '<', $value);
                break;
            case '>=':
                $query->where($field, '>=', $value);
                break;
            case '<=':
                $query->where($field, '<=', $value);
                break;
            case 'like':
                $query->where($field, 'like', '%'.$value.'%');
                break;
            case 'starts_with':
                $query->where($field, 'like', $value.'%');
                break;
            case 'ends_with':
                $query->where($field, 'like', '%'.$value);
                break;
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween($field, $value);
                }
                break;
            case 'in':
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                }
                break;
        }
    }

    protected const IDADE_MAXIMA_SIGTAP = 150;

    protected function formatIdadeExibicao($idade): string
    {
        if ($idade === null || $idade === '') {
            return 'Ignorado';
        }

        $n = (int) $idade;

        if ($n > self::IDADE_MAXIMA_SIGTAP) {
            return 'Ignorado';
        }

        return number_format($n, 0, ',', '.');
    }

    protected function idadeAgrupamentoKey($idade): string
    {
        if ($idade === null || $idade === '') {
            return 'Ignorado';
        }

        $n = (int) $idade;

        if ($n > self::IDADE_MAXIMA_SIGTAP) {
            return 'Ignorado';
        }

        return (string) $n;
    }

    protected function idadeNormalizadaSql(string $column): string
    {
        $max = self::IDADE_MAXIMA_SIGTAP;

        return "CASE WHEN CAST({$column} AS SIGNED) > {$max} THEN NULL ELSE {$column} END";
    }
}
