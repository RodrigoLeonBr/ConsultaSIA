<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MatrixReportExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $matrixData;
    protected $numericFields;

    public function __construct($matrixData, $numericFields = [])
    {
        $this->matrixData = $matrixData;
        $this->numericFields = $numericFields;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $data = [];
        
        // Add data rows
        foreach ($this->matrixData['rows'] as $row) {
            $rowData = [$row['category']];
            
            // Add values for each competencia
            foreach ($this->matrixData['competencias'] as $comp) {
                $values = $row['values'][$comp['code']] ?? [];
                $cellValue = '';
                
                foreach ($this->numericFields as $field) {
                    $value = $values[$field] ?? 0;
                    if ($field === 'PRD_VL_P') {
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
                if ($field === 'PRD_VL_P') {
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
                if ($field === 'PRD_VL_P') {
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
            if ($field === 'PRD_VL_P') {
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
