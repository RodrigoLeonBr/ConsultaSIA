<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RelatorioExport implements FromCollection, WithHeadings, WithStyles
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
                return collect([]);
            }
            
            $collection = $dataCollection->map(function ($row) {
                // Ensure row is an array
                if (is_object($row)) {
                    $row = (array) $row;
                }
                return array_values($row);
            });
            
            // Add totals if available
            if (!empty($this->totals)) {
                $collection->push([]); // Empty row
                $collection->push(['TOTAIS']);
                foreach ($this->totals as $label => $value) {
                    $collection->push([$label, $value]);
                }
            }
            
            return $collection;
        } catch (\Exception $e) {
            \Log::error('Error in RelatorioExport collection: ' . $e->getMessage());
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
                
                return array_keys($firstRow);
            }
            
            return [];
        } catch (\Exception $e) {
            \Log::error('Error in RelatorioExport headings: ' . $e->getMessage());
            return [];
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
}