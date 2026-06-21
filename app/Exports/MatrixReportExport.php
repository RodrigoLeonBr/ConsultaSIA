<?php

namespace App\Exports;

use App\Support\BrazilianNumberFormatter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MatrixReportExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithColumnFormatting
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
                $rowData[] = BrazilianNumberFormatter::formatMatrixExportValues(
                    $this->numericFields,
                    $values
                );
            }

            $rowData[] = BrazilianNumberFormatter::formatMatrixExportValues(
                $this->numericFields,
                $row['totals'] ?? []
            );
            
            $data[] = $rowData;
        }
        
        // Add totals row
        $totalsRow = ['TOTAL'];
        foreach ($this->matrixData['competencias'] as $comp) {
            $totals = $this->matrixData['totals'][$comp['code']] ?? [];
            $totalsRow[] = BrazilianNumberFormatter::formatMatrixExportValues(
                $this->numericFields,
                $totals
            );
        }

        $totalsRow[] = BrazilianNumberFormatter::formatMatrixExportValues(
            $this->numericFields,
            $this->matrixData['grand_totals'] ?? []
        );
        
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
    public function columnFormats(): array
    {
        if (count($this->numericFields) !== 1) {
            return [];
        }

        $format = BrazilianNumberFormatter::excelFormatForField($this->numericFields[0]);
        if ($format === null) {
            return [];
        }

        $formats = [];
        $lastColumn = count($this->matrixData['competencias']) + 2;

        for ($columnIndex = 2; $columnIndex <= $lastColumn; $columnIndex++) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $formats[$columnLetter] = $format;
        }

        return $formats;
    }

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
