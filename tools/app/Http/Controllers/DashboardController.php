<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with dynamic statistics
     */
    public function index()
    {
        try {
            // Get statistics
            $stats = $this->getSystemStatistics();
            
            // Get recent activities for timeline
            $recentActivities = $this->getRecentActivitiesForTimeline();
            
            return view('dashboard-modern', compact('stats', 'recentActivities'));
        } catch (\Exception $e) {
            \Log::error('Error loading dashboard: ' . $e->getMessage());
            
            // Return dashboard with default stats in case of error
            $stats = [
                'prestadores' => 0,
                'procedimentos' => 0,
                'cbo_count' => 0,
                'financiamentos' => [
                    'ultimo_periodo' => 'N/A',
                    'total_ano' => 'R$ 0,00'
                ]
            ];
            
            $recentActivities = [];
            
            return view('dashboard-modern', compact('stats', 'recentActivities'));
        }
    }

    /**
     * Get system statistics from database
     */
    private function getSystemStatistics()
    {
        // Count prestadores ativos
        $prestadores = DB::table('prestador')
            ->where('ativo', 1)
            ->count();

        // Count procedimentos
        $procedimentos = DB::table('procedimento')->count();

        // Count CBO
        $cbo_count = DB::table('cbo')->count();

        // Get financiamentos info
        $financiamentos = $this->getFinanciamentosInfo();

        return [
            'prestadores' => $prestadores,
            'procedimentos' => $procedimentos,
            'cbo_count' => $cbo_count,
            'financiamentos' => $financiamentos
        ];
    }

    /**
     * Get financiamentos information
     */
    private function getFinanciamentosInfo()
    {
        try {
            // Get the latest period with production (6 digits format YYYYMM)
            $ultimoPeriodo = DB::table('s_prd')
                ->select('prd_cmp')
                ->whereNotNull('prd_cmp')
                ->where('prd_cmp', '!=', '')
                ->whereRaw('LENGTH(prd_cmp) = 6')
                ->whereRaw('prd_cmp REGEXP "^[0-9]{6}$"')
                ->orderBy('prd_cmp', 'desc')
                ->first();

            if (!$ultimoPeriodo) {
                return [
                    'ultimo_periodo' => 'N/A',
                    'total_ano' => 'R$ 0,00'
                ];
            }

            // Format the period (YYYYMM to YYYY-MM)
            $periodo = $ultimoPeriodo->prd_cmp;
            
            // Handle different period formats
            if (strlen($periodo) >= 6) {
                $ano = substr($periodo, 0, 4);
                $mes = substr($periodo, 4, 2);
                $periodoFormatado = $ano . '-' . $mes;
            } else {
                // Handle incomplete periods
                $periodoFormatado = $periodo;
                $ano = substr($periodo, 0, 4);
            }

            // Get total value for the year
            $totalAno = DB::table('s_prd')
                ->where('prd_cmp', 'like', $ano . '%')
                ->whereNotNull('PRD_VL_P')
                ->sum(DB::raw('CAST(PRD_VL_P as DECIMAL(15,2))'));

            return [
                'ultimo_periodo' => $periodoFormatado,
                'total_ano' => (float) $totalAno
            ];

        } catch (\Exception $e) {
            \Log::error('Error getting financiamentos info: ' . $e->getMessage());
            
            return [
                'ultimo_periodo' => 'Erro',
                'total_ano' => 0.0
            ];
        }
    }

    /**
     * Get recent activities for timeline
     */
    private function getRecentActivitiesForTimeline()
    {
        try {
            // Simulate recent activities (in a real app, this would come from an activity log)
            return [
                [
                    'type' => 'created',
                    'title' => 'Novo prestador cadastrado',
                    'description' => 'Hospital Central foi adicionado ao sistema',
                    'time' => 'há 2 horas',
                    'user' => 'Admin'
                ],
                [
                    'type' => 'updated',
                    'title' => 'Procedimento atualizado',
                    'description' => 'Consulta médica - valores atualizados',
                    'time' => 'há 4 horas',
                    'user' => 'Operador'
                ],
                [
                    'type' => 'approved',
                    'title' => 'APAC aprovada',
                    'description' => 'Autorização para procedimento de alta complexidade',
                    'time' => 'ontem',
                    'user' => 'Analista'
                ],
                [
                    'type' => 'created',
                    'title' => 'Relatório gerado',
                    'description' => 'Relatório de produção do último período',
                    'time' => 'há 2 dias',
                    'user' => 'Sistema'
                ],
                [
                    'type' => 'updated',
                    'title' => 'CBO atualizado',
                    'description' => 'Código de ocupação médica revisado',
                    'time' => 'há 3 dias',
                    'user' => 'Admin'
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('Error getting recent activities: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent activity data
     */
    public function getRecentActivity()
    {
        try {
            // Get recent production data
            $recentProduction = DB::table('s_prd as sp')
                ->join('prestador as pr', 'sp.prd_uid', '=', 'pr.re_cunid')
                ->select([
                    'sp.prd_cmp',
                    'pr.re_cnome',
                    DB::raw('SUM(CAST(sp.PRD_QT_P as UNSIGNED)) as total_quantidade'),
                    DB::raw('SUM(CAST(sp.PRD_VL_P as DECIMAL(15,2))) as total_valor')
                ])
                ->whereNotNull('sp.prd_cmp')
                ->where('sp.prd_cmp', '!=', '')
                ->groupBy('sp.prd_cmp', 'pr.re_cnome')
                ->orderBy('sp.prd_cmp', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'recent_production' => $recentProduction
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao carregar atividade recente: ' . $e->getMessage()
            ], 500);
        }
    }
}