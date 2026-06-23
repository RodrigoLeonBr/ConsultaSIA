<?php

namespace App\Exports;

use App\Exports\Concerns\FormatsBrazilianExcelColumns;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RelatorioExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    use FormatsBrazilianExcelColumns;

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
            $dataCollection = collect($this->data);

            if ($dataCollection->isEmpty()) {
                return collect([]);
            }

            $collection = $dataCollection->map(function ($row) {
                if (is_object($row)) {
                    $row = (array) $row;
                }

                $processedRow = [];
                foreach ($row as $value) {
                    $processedRow[] = $this->processValueForExcel($value);
                }

                return $processedRow;
            });

            if (!empty($this->totals)) {
                $collection->push([]);
                $collection->push(['TOTAIS']);
                foreach ($this->totals as $label => $value) {
                    $collection->push([$label, $this->processValueForExcel($value)]);
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

    public function columnFormats(): array
    {
        return $this->columnFormatsForHeaders($this->headings());
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
