<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            'siaCmp'  => $this->lastCompetencia('s_prd', 'prd_cmp'),
            'sihCmp'  => $this->lastCompetencia('s_aih', 'COMPETENCIA'),
        ]);
    }

    private function lastCompetencia(string $table, string $column): ?string
    {
        try {
            $row = DB::table($table)
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->whereRaw("LENGTH($column) = 6")
                ->whereRaw("$column REGEXP '^[0-9]{6}$'")
                ->orderBy($column, 'desc')
                ->value($column);

            if (!$row) {
                return null;
            }

            // YYYYMM → MM/YYYY
            return substr($row, 4, 2) . '/' . substr($row, 0, 4);
        } catch (\Exception $e) {
            \Log::warning("lastCompetencia($table.$column): " . $e->getMessage());
            return null;
        }
    }
}
