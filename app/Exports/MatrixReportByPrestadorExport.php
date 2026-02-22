<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MatrixReportByPrestadorExport implements WithMultipleSheets
{
    protected $matrixData;
    protected $numericFields;
    protected $prestadorField;

    public function __construct($matrixData, $numericFields = [], $prestadorField = null)
    {
        $this->matrixData = $matrixData;
        $this->numericFields = $numericFields;
        $this->prestadorField = $prestadorField;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        
        // Se não há campo de prestador, retornar uma única planilha
        if (!$this->prestadorField || !isset($this->matrixData['prestadores'])) {
            $sheets[] = new MatrixReportSheet($this->matrixData, $this->numericFields, 'Todos os Prestadores');
            return $sheets;
        }
        
        $prestadores = $this->matrixData['prestadores'];
        $prestadoresCount = count($prestadores);
        
        // Criar uma planilha para cada prestador
        foreach ($prestadores as $prestadorCode => $prestadorData) {
            $prestadorNome = $prestadorData['nome'] ?? "Prestador {$prestadorCode}";
            $sheets[] = new MatrixReportSheet($prestadorData, $this->numericFields, $prestadorNome);
        }
        
        // Se há múltiplos prestadores, criar planilha "TOTAL" agregada
        if ($prestadoresCount > 1) {
            $totalMatrix = $this->aggregatePrestadoresData($prestadores);
            $sheets[] = new MatrixReportSheet($totalMatrix, $this->numericFields, 'TOTAL');
        }
        
        return $sheets;
    }
    
    /**
     * Agregar dados de todos os prestadores em uma única matriz
     */
    protected function aggregatePrestadoresData($prestadores)
    {
        if (empty($prestadores)) {
            return [];
        }
        
        // Pegar estrutura base do primeiro prestador
        $firstPrestador = reset($prestadores);
        $competencias = $firstPrestador['competencias'] ?? [];
        
        // Estrutura para agregar todos os dados
        $aggregated = [
            'competencias' => $competencias,
            'rows' => [],
            'totals' => [],
            'grand_totals' => []
        ];
        
        // Agregar todas as linhas de todos os prestadores
        $rowsMap = [];
        
        foreach ($prestadores as $prestadorData) {
            if (!isset($prestadorData['rows'])) {
                continue;
            }
            
            foreach ($prestadorData['rows'] as $row) {
                $category = $row['category'];
                
                // Inicializar linha se não existir
                if (!isset($rowsMap[$category])) {
                    $rowsMap[$category] = [
                        'category' => $category,
                        'values' => [],
                        'totals' => []
                    ];
                    
                    // Inicializar valores para todas as competências
                    foreach ($competencias as $comp) {
                        $rowsMap[$category]['values'][$comp['code']] = [];
                        foreach ($this->numericFields as $field) {
                            $rowsMap[$category]['values'][$comp['code']][$field] = 0;
                        }
                    }
                    
                    // Inicializar totais
                    foreach ($this->numericFields as $field) {
                        $rowsMap[$category]['totals'][$field] = 0;
                    }
                }
                
                // Somar valores de cada competência
                foreach ($competencias as $comp) {
                    $compCode = $comp['code'];
                    if (isset($row['values'][$compCode])) {
                        foreach ($this->numericFields as $field) {
                            $value = $row['values'][$compCode][$field] ?? 0;
                            $rowsMap[$category]['values'][$compCode][$field] += $value;
                        }
                    }
                }
                
                // Somar totais da linha
                if (isset($row['totals'])) {
                    foreach ($this->numericFields as $field) {
                        $value = $row['totals'][$field] ?? 0;
                        $rowsMap[$category]['totals'][$field] += $value;
                    }
                }
            }
        }
        
        // Converter mapa em array
        $aggregated['rows'] = array_values($rowsMap);
        
        // Calcular totais das colunas
        foreach ($competencias as $comp) {
            $compCode = $comp['code'];
            $aggregated['totals'][$compCode] = [];
            
            foreach ($this->numericFields as $field) {
                $aggregated['totals'][$compCode][$field] = 0;
                
                foreach ($aggregated['rows'] as $row) {
                    $aggregated['totals'][$compCode][$field] += $row['values'][$compCode][$field] ?? 0;
                }
            }
        }
        
        // Calcular total geral
        foreach ($this->numericFields as $field) {
            $aggregated['grand_totals'][$field] = 0;
            
            foreach ($competencias as $comp) {
                $compCode = $comp['code'];
                $aggregated['grand_totals'][$field] += $aggregated['totals'][$compCode][$field] ?? 0;
            }
        }
        
        return $aggregated;
    }
}

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MatrixReportSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $matrixData;
    protected $numericFields;
    protected $sheetTitle;

    public function __construct($matrixData, $numericFields = [], $sheetTitle = 'Matriz')
    {
        $this->matrixData = $matrixData;
        $this->numericFields = $numericFields;
        $this->sheetTitle = $sheetTitle;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        // Limitar título a 31 caracteres (limite do Excel)
        return mb_substr($this->sheetTitle, 0, 31);
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $data = [];
        
        if (!isset($this->matrixData['rows']) || empty($this->matrixData['rows'])) {
            return $data;
        }
        
        // Add data rows
        foreach ($this->matrixData['rows'] as $row) {
            $rowData = [$row['category']];
            
            // Add values for each competencia
            foreach ($this->matrixData['competencias'] as $comp) {
                $values = $row['values'][$comp['code']] ?? [];
                $cellValue = '';
                
                foreach ($this->numericFields as $field) {
                    $value = $values[$field] ?? 0;
                    if ($field === 'PRD_VL_P' || $field === 'PAP_VALOR') {
                        $cellValue .= number_format($value, 2, ',', '.');
                    } else {
                        $cellValue .= number_format($value, 0, ',', '.');
                    }
                    if (count($this->numericFields) > 1) {
                        $cellValue .= ' / ';
                    }
                }
                
                $rowData[] = rtrim($cellValue, ' / ') ?: '0';
            }
            
            // Add row total
            $totalValue = '';
            foreach ($this->numericFields as $field) {
                $value = $row['totals'][$field] ?? 0;
                if ($field === 'PRD_VL_P' || $field === 'PAP_VALOR') {
                    $totalValue .= number_format($value, 2, ',', '.');
                } else {
                    $totalValue .= number_format($value, 0, ',', '.');
                }
                if (count($this->numericFields) > 1) {
                    $totalValue .= ' / ';
                }
            }
            $rowData[] = rtrim($totalValue, ' / ') ?: '0';
            
            $data[] = $rowData;
        }
        
        // Add totals row
        $totalsRow = ['TOTAL'];
        foreach ($this->matrixData['competencias'] as $comp) {
            $totals = $this->matrixData['totals'][$comp['code']] ?? [];
            $totalValue = '';
            
            foreach ($this->numericFields as $field) {
                $value = $totals[$field] ?? 0;
                if ($field === 'PRD_VL_P' || $field === 'PAP_VALOR') {
                    $totalValue .= number_format($value, 2, ',', '.');
                } else {
                    $totalValue .= number_format($value, 0, ',', '.');
                }
                if (count($this->numericFields) > 1) {
                    $totalValue .= ' / ';
                }
            }
            $totalsRow[] = rtrim($totalValue, ' / ') ?: '0';
        }
        
        // Grand total
        $grandTotalValue = '';
        foreach ($this->numericFields as $field) {
            $value = $this->matrixData['grand_totals'][$field] ?? 0;
            if ($field === 'PRD_VL_P' || $field === 'PAP_VALOR') {
                $grandTotalValue .= number_format($value, 2, ',', '.');
            } else {
                $grandTotalValue .= number_format($value, 0, ',', '.');
            }
            if (count($this->numericFields) > 1) {
                $grandTotalValue .= ' / ';
            }
        }
        $totalsRow[] = rtrim($grandTotalValue, ' / ') ?: '0';
        
        $data[] = $totalsRow;
        
        return $data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $headings = ['Categoria'];
        
        // Add competencia headings
        foreach ($this->matrixData['competencias'] as $comp) {
            $headings[] = $comp['label'];
        }
        
        $headings[] = 'Total';
        
        return $headings;
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->matrixData['rows']) + 2; // +1 for header, +1 for totals
        $lastColumn = count($this->matrixData['competencias']) + 2; // +1 for category, +1 for total
        $lastColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumn);
        
        return [
            // Header row
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            
            // Totals row
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DBEAFE']
                ],
            ],
            
            // All cells border
            "A1:{$lastColumnLetter}{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
            
            // Category column
            "A:A" => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                ],
            ],
            
            // Data columns alignment
            "B:{$lastColumnLetter}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        $widths = ['A' => 30]; // Category column
        
        // Competencia columns
        $columnIndex = 2;
        foreach ($this->matrixData['competencias'] as $comp) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $widths[$columnLetter] = 15;
            $columnIndex++;
        }
        
        // Total column
        $totalColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
        $widths[$totalColumnLetter] = 18;
        
        return $widths;
    }
}

