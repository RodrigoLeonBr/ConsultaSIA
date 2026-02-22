<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RelatorioApacExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, WithColumnWidths
{
    protected $data;
    protected $selectedFields;
    protected $totals;

    public function __construct($data, $selectedFields, $totals = [])
    {
        $this->data = $data;
        $this->selectedFields = $selectedFields;
        $this->totals = $totals;
    }

    public function collection()
    {
        try {
            // Convert data to collection if it isn't already
            $dataCollection = collect($this->data);
            
            if ($dataCollection->isEmpty()) {
                \Log::warning('RelatorioApacExport: Empty data collection');
                return collect([]);
            }
            
            $collection = $dataCollection->map(function ($row) {
                // Ensure row is an array
                if (is_object($row)) {
                    $row = (array) $row;
                }
                
                // Process values for better Excel formatting
                $processedRow = [];
                foreach ($row as $key => $value) {
                    $processedRow[] = $this->processValue($key, $value);
                }
                
                return $processedRow;
            });
            
            // Add totals if available
            if (!empty($this->totals)) {
                $collection->push([]); // Empty row
                $collection->push(['TOTAIS']);
                foreach ($this->totals as $label => $value) {
                    $collection->push([$label, $this->processValue($label, $value)]);
                }
            }
            
            \Log::info('RelatorioApacExport: Collection processed successfully', [
                'rows' => $collection->count()
            ]);
            
            return $collection;
            
        } catch (\Exception $e) {
            \Log::error('Error in RelatorioApacExport collection: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return collect([]);
        }
    }

    public function headings(): array
    {
        try {
            $dataCollection = collect($this->data);
            
            if ($dataCollection->isNotEmpty()) {
                $firstRow = $dataCollection->first();
                
                // Ensure first row is an array
                if (is_object($firstRow)) {
                    $firstRow = (array) $firstRow;
                }
                
                $headers = array_keys($firstRow);
                \Log::info('RelatorioApacExport: Headers generated', ['headers' => $headers]);
                
                return $headers;
            }
            
            \Log::warning('RelatorioApacExport: No data for headers');
            return [];
            
        } catch (\Exception $e) {
            \Log::error('Error in RelatorioApacExport headings: ' . $e->getMessage());
            return [];
        }
    }

    public function styles(Worksheet $sheet)
    {
        try {
            $lastRow = $sheet->getHighestRow();
            $lastColumn = $sheet->getHighestColumn();
            
            return [
                // Header row styling
                1 => [
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '28A745'] // Verde para APAC
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ],
                
                // Data rows styling
                "2:{$lastRow}" => [
                    'font' => [
                        'size' => 9,
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => false
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC']
                        ]
                    ]
                ],
                
                // Totals section styling
                $this->getTotalsRowRange($lastRow) => [
                    'font' => [
                        'bold' => true,
                        'size' => 9,
                        'color' => ['rgb' => '000000']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D4EDDA'] // Verde claro para totais APAC
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error in RelatorioApacExport styles: ' . $e->getMessage());
            return [];
        }
    }

    public function columnFormats(): array
    {
        try {
            $formats = [];
            $headers = $this->headings();
            
            foreach ($headers as $index => $header) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                
                if ($this->isCurrencyColumn($header)) {
                    $formats[$columnLetter] = NumberFormat::FORMAT_CURRENCY_BRL;
                } elseif ($this->isNumberColumn($header)) {
                    $formats[$columnLetter] = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;
                } elseif ($this->isPercentageColumn($header)) {
                    $formats[$columnLetter] = NumberFormat::FORMAT_PERCENTAGE_00;
                }
            }
            
            return $formats;
            
        } catch (\Exception $e) {
            \Log::error('Error in RelatorioApacExport columnFormats: ' . $e->getMessage());
            return [];
        }
    }

    public function columnWidths(): array
    {
        try {
            $widths = [];
            $headers = $this->headings();
            
            foreach ($headers as $index => $header) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                
                if ($this->isCurrencyColumn($header)) {
                    $widths[$columnLetter] = 15;
                } elseif ($this->isNumberColumn($header)) {
                    $widths[$columnLetter] = 12;
                } elseif (str_contains(strtolower($header), 'descrição') || str_contains(strtolower($header), 'procedimento')) {
                    $widths[$columnLetter] = 25;
                } elseif (str_contains(strtolower($header), 'código') || str_contains(strtolower($header), 'cnes')) {
                    $widths[$columnLetter] = 10;
                } elseif (str_contains(strtolower($header), 'cismetro')) {
                    if (str_contains(strtolower($header), 'descrição')) {
                        $widths[$columnLetter] = 30;
                    } else {
                        $widths[$columnLetter] = 18;
                    }
                } else {
                    $widths[$columnLetter] = 15;
                }
            }
            
            return $widths;
            
        } catch (\Exception $e) {
            \Log::error('Error in RelatorioApacExport columnWidths: ' . $e->getMessage());
            return [];
        }
    }

    private function processValue($key, $value)
    {
        try {
            if (is_string($value) && str_starts_with($value, 'R$')) {
                $numericValue = preg_replace('/[^\d,.-]/', '', $value);
                $numericValue = str_replace(['.', ','], ['', '.'], $numericValue);
                return (float) $numericValue;
            }
            
            if (is_string($value) && preg_match('/^\d{1,3}(\.\d{3})*$/', $value)) {
                return (int) str_replace('.', '', $value);
            }
            
            return $value;
            
        } catch (\Exception $e) {
            \Log::error('Error processing value in RelatorioApacExport: ' . $e->getMessage());
            return $value;
        }
    }

    private function isCurrencyColumn($header)
    {
        $currencyKeywords = [
            'valor', 'cismetro - valor', 'total', 'unitário', 
            'cismetro - valor unitário', 'cismetro - valor total',
            'pap_valor', 'valor unitário', 'valor total'
        ];
        
        foreach ($currencyKeywords as $keyword) {
            if (str_contains(strtolower($header), strtolower($keyword))) {
                return true;
            }
        }
        
        return false;
    }

    private function isNumberColumn($header)
    {
        $numberKeywords = [
            'quantidade', 'total', 'qtd', 'número', 'codigo',
            'pap_qt_p', 'quantidade produzida', 'quantidade total'
        ];
        
        foreach ($numberKeywords as $keyword) {
            if (str_contains(strtolower($header), strtolower($keyword))) {
                return true;
            }
        }
        
        return false;
    }

    private function isPercentageColumn($header)
    {
        return str_contains(strtolower($header), 'percentual') || 
               str_contains(strtolower($header), '%');
    }

    private function getTotalsRowRange($lastRow)
    {
        if (!empty($this->totals)) {
            $totalsStartRow = $lastRow - count($this->totals) - 1;
            return "{$totalsStartRow}:{$lastRow}";
        }
        
        return "1:1";
    }
}