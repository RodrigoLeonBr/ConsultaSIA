<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RelatorioApacExport implements FromCollection, WithHeadings
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
            \Log::info('RelatorioApacExport: Starting collection method');
            
            // Convert data to collection if it isn't already
            $dataCollection = collect($this->data);
            
            if ($dataCollection->isEmpty()) {
                \Log::warning('RelatorioApacExport: Empty data collection');
                return collect([]);
            }
            
            \Log::info('RelatorioApacExport: Processing ' . $dataCollection->count() . ' rows');
            
            // Simple mapping - just get values
            $collection = $dataCollection->map(function ($row) {
                // Ensure row is an array
                if (is_object($row)) {
                    $row = (array) $row;
                }
                
                // Return values as-is
                return array_values($row);
            });
            
            // Add totals if available
            if (!empty($this->totals)) {
                $collection->push([]); // Empty row
                $collection->push(['=== TOTAIS ===']);
                foreach ($this->totals as $label => $value) {
                    $collection->push([$label, $value]);
                }
            }
            
            \Log::info('RelatorioApacExport: Collection processed successfully with ' . $collection->count() . ' total rows');
            
            return $collection;
            
        } catch (\Exception $e) {
            \Log::error('Error in RelatorioApacExport collection: ' . $e->getMessage());
            return collect([]);
        }
    }

    public function headings(): array
    {
        try {
            \Log::info('RelatorioApacExport: Starting headings method');
            
            $dataCollection = collect($this->data);
            
            if ($dataCollection->isNotEmpty()) {
                $firstRow = $dataCollection->first();
                
                // Ensure first row is an array
                if (is_object($firstRow)) {
                    $firstRow = (array) $firstRow;
                }
                
                $headers = array_keys($firstRow);
                \Log::info('RelatorioApacExport: Generated ' . count($headers) . ' headers');
                
                return $headers;
            }
            
            \Log::warning('RelatorioApacExport: No data for headers');
            return [];
            
        } catch (\Exception $e) {
            \Log::error('Error in RelatorioApacExport headings: ' . $e->getMessage());
            return [];
        }
    }
}