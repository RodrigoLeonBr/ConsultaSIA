<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaturamentoPrestadorController extends Controller
{
    /**
     * Exibe a tela de filtros para o relatório de faturamento por prestador
     */
    public function index()
    {
        // Buscar competências disponíveis (última competência como padrão)
        $ultimaCompetencia = DB::table('s_prd')
            ->select('prd_cmp')
            ->whereNotNull('prd_cmp')
            ->where('prd_cmp', '!=', '')
            ->whereRaw('LENGTH(prd_cmp) = 6')
            ->whereRaw('prd_cmp REGEXP "^[0-9]{6}$"')
            ->orderBy('prd_cmp', 'desc')
            ->first();

        $competencias = DB::table('s_prd')
            ->select('prd_cmp')
            ->distinct()
            ->whereNotNull('prd_cmp')
            ->where('prd_cmp', '!=', '')
            ->whereRaw('LENGTH(prd_cmp) = 6')
            ->whereRaw('prd_cmp REGEXP "^[0-9]{6}$"')
            ->orderBy('prd_cmp', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->prd_cmp,
                    'label' => $this->formatCompetencia($item->prd_cmp),
                ];
            });

        // Buscar prestadores ativos (tipo P)
        $prestadores = DB::table('prestador')
            ->where('ativo', true)
            ->orderBy('re_cnome')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->re_cunid,
                    'label' => $item->re_cunid.' - '.$item->re_cnome,
                ];
            });

        // Adicionar opção "Todos"
        $prestadores->prepend([
            'value' => '',
            'label' => 'Todos os Prestadores',
        ]);

        return view('relatorios.faturamento-prestador', compact(
            'competencias',
            'prestadores',
            'ultimaCompetencia'
        ));
    }

    /**
     * Gera o relatório de faturamento por prestador com estrutura hierárquica
     */
    public function gerar(Request $request)
    {
        $request->validate([
            'competencia' => 'required|string|size:6',
            'prestador_id' => 'nullable|string',
        ]);

        $competencia = $request->competencia;
        $prestadorId = $request->prestador_id;

        // Query principal seguindo exatamente a estrutura solicitada
        $query = DB::table('s_prd as sp')
            ->leftJoin('prestador as pr', 'sp.prd_uid', '=', 'pr.re_cunid')
            ->leftJoin('procedimento as proc', 'sp.prd_pa', '=', 'proc.codigo')
            ->leftJoin('forma as f_grupo', function ($join) {
                $join->on(DB::raw('SUBSTRING(sp.prd_pa, 1, 2)'), '=', 'f_grupo.grupo')
                    ->where('f_grupo.subgrupo', '=', DB::raw('CONCAT(SUBSTRING(sp.prd_pa, 1, 2), "00")'))
                    ->where('f_grupo.forma', '=', DB::raw('CONCAT(SUBSTRING(sp.prd_pa, 1, 2), "0000")'));
            })
            ->leftJoin('forma as f_subgrupo', function ($join) {
                $join->on(DB::raw('SUBSTRING(sp.prd_pa, 1, 4)'), '=', 'f_subgrupo.subgrupo')
                    ->where('f_subgrupo.forma', '=', DB::raw('CONCAT(SUBSTRING(sp.prd_pa, 1, 4), "00")'));
            })
            ->leftJoin('forma as f_forma', function ($join) {
                $join->on(DB::raw('SUBSTRING(sp.prd_pa, 1, 6)'), '=', 'f_forma.forma');
            })
            ->select([
                // Campos chave conforme especificação
                'pr.re_cunid as prestador_codigo',
                'pr.re_cnome as prestador_nome',
                'sp.prd_rub as tipo_financiamento',
                DB::raw('SUBSTRING(sp.prd_pa, 1, 2) as grupo_codigo'),
                'f_grupo.descricao as grupo_descricao',
                DB::raw('SUBSTRING(sp.prd_pa, 1, 4) as subgrupo_codigo'),
                'f_subgrupo.descricao as subgrupo_descricao',
                DB::raw('SUBSTRING(sp.prd_pa, 1, 6) as forma_codigo'),
                'f_forma.descricao as forma_descricao',
                'sp.prd_pa as procedimento_codigo',
                'proc.procedimento as procedimento_nome', // Corrigido: proc.procedimento em vez de proc.nome
                'proc.PA_TOTAL as valor_unitario', // Adicionado valor unitário
                // Quantidades e valores com SUM para totalização
                DB::raw('SUM(CAST(sp.PRD_QT_P as UNSIGNED)) as quantidade_apresentada'),
                DB::raw('SUM(CAST(sp.PRD_QT_P as UNSIGNED) * CAST(proc.PA_TOTAL as DECIMAL(15,2))) as valor_apresentado'),
                DB::raw('SUM(CAST(sp.PRD_QT_A as UNSIGNED)) as quantidade_aprovada'),
                DB::raw('SUM(CAST(sp.PRD_VL_A as DECIMAL(15,2))) as valor_aprovado'),
            ])
            ->where('sp.prd_cmp', $competencia);

        // Filtro por prestador específico se fornecido
        if ($prestadorId) {
            $query->where('sp.prd_uid', $prestadorId);
        }

        $dados = $query->groupBy([
            'pr.re_cunid',
            'pr.re_cnome',
            'sp.prd_rub',
            'grupo_codigo',
            'f_grupo.descricao',
            'subgrupo_codigo',
            'f_subgrupo.descricao',
            'forma_codigo',
            'f_forma.descricao',
            'sp.prd_pa',
            'proc.procedimento',
            'proc.PA_TOTAL',
        ])
            ->orderBy('pr.re_cnome')
            ->orderBy('sp.prd_rub')
            ->orderBy('grupo_codigo')
            ->orderBy('subgrupo_codigo')
            ->orderBy('forma_codigo')
            ->orderBy('sp.prd_pa')
            ->get();

        // Processar dados para estrutura hierárquica conforme especificação
        $dadosProcessados = $this->processarDadosHierarquicos($dados);

        $competenciaFormatada = $this->formatCompetencia($competencia);

        return view('relatorios.faturamento-prestador-resultado', compact(
            'dadosProcessados',
            'competenciaFormatada',
            'competencia',
            'prestadorId'
        ));
    }

    /**
     * Exporta o relatório em PDF com quebra de página por prestador
     */
    public function exportarPdf(Request $request)
    {
        $request->validate([
            'competencia' => 'required|string|size:6',
            'prestador_id' => 'nullable|string',
        ]);

        $competencia = $request->competencia;
        $prestadorId = $request->prestador_id;

        // Reutilizar a mesma lógica do método gerar
        $query = DB::table('s_prd as sp')
            ->leftJoin('prestador as pr', 'sp.prd_uid', '=', 'pr.re_cunid')
            ->leftJoin('procedimento as proc', 'sp.prd_pa', '=', 'proc.codigo')
            ->leftJoin('forma as f_grupo', function ($join) {
                $join->on(DB::raw('SUBSTRING(sp.prd_pa, 1, 2)'), '=', 'f_grupo.grupo')
                    ->where('f_grupo.subgrupo', '=', DB::raw('CONCAT(SUBSTRING(sp.prd_pa, 1, 2), "00")'))
                    ->where('f_grupo.forma', '=', DB::raw('CONCAT(SUBSTRING(sp.prd_pa, 1, 2), "0000")'));
            })
            ->leftJoin('forma as f_subgrupo', function ($join) {
                $join->on(DB::raw('SUBSTRING(sp.prd_pa, 1, 4)'), '=', 'f_subgrupo.subgrupo')
                    ->where('f_subgrupo.forma', '=', DB::raw('CONCAT(SUBSTRING(sp.prd_pa, 1, 4), "00")'));
            })
            ->leftJoin('forma as f_forma', function ($join) {
                $join->on(DB::raw('SUBSTRING(sp.prd_pa, 1, 6)'), '=', 'f_forma.forma');
            })
            ->select([
                'pr.re_cunid as prestador_codigo',
                'pr.re_cnome as prestador_nome',
                'sp.prd_rub as tipo_financiamento',
                DB::raw('SUBSTRING(sp.prd_pa, 1, 2) as grupo_codigo'),
                'f_grupo.descricao as grupo_descricao',
                DB::raw('SUBSTRING(sp.prd_pa, 1, 4) as subgrupo_codigo'),
                'f_subgrupo.descricao as subgrupo_descricao',
                DB::raw('SUBSTRING(sp.prd_pa, 1, 6) as forma_codigo'),
                'f_forma.descricao as forma_descricao',
                'sp.prd_pa as procedimento_codigo',
                'proc.procedimento as procedimento_nome',
                'proc.PA_TOTAL as valor_unitario',
                DB::raw('SUM(CAST(sp.PRD_QT_P as UNSIGNED)) as quantidade_apresentada'),
                DB::raw('SUM(CAST(sp.PRD_QT_P as UNSIGNED) * CAST(proc.PA_TOTAL as DECIMAL(15,2))) as valor_apresentado'),
                DB::raw('SUM(CAST(sp.PRD_QT_A as UNSIGNED)) as quantidade_aprovada'),
                DB::raw('SUM(CAST(sp.PRD_VL_A as DECIMAL(15,2))) as valor_aprovado'),
            ])
            ->where('sp.prd_cmp', $competencia);

        if ($prestadorId) {
            $query->where('sp.prd_uid', $prestadorId);
        }

        $dados = $query->groupBy([
            'pr.re_cunid',
            'pr.re_cnome',
            'sp.prd_rub',
            'grupo_codigo',
            'f_grupo.descricao',
            'subgrupo_codigo',
            'f_subgrupo.descricao',
            'forma_codigo',
            'f_forma.descricao',
            'sp.prd_pa',
            'proc.procedimento',
            'proc.PA_TOTAL',
        ])
            ->orderBy('pr.re_cnome')
            ->orderBy('sp.prd_rub')
            ->orderBy('grupo_codigo')
            ->orderBy('subgrupo_codigo')
            ->orderBy('forma_codigo')
            ->orderBy('sp.prd_pa')
            ->get();

        $dadosProcessados = $this->processarDadosHierarquicos($dados);
        $competenciaFormatada = $this->formatCompetencia($competencia);

        // Configurar PDF com quebra de página por prestador
        $pdf = Pdf::loadView('relatorios.faturamento-prestador-pdf', compact(
            'dadosProcessados',
            'competenciaFormatada',
            'competencia',
            'prestadorId'
        ));

        $pdf->setPaper('A4', 'portrait');

        // Configurar margens e quebra de página
        $pdf->getDomPDF()->getOptions()->set([
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
        ]);

        $nomeArquivo = 'faturamento-prestador-'.$competencia;
        if ($prestadorId) {
            $prestador = DB::table('prestador')->where('re_cunid', $prestadorId)->first();
            $nomeArquivo .= '-'.($prestador ? $prestador->re_cunid : $prestadorId);
        }

        return $pdf->download($nomeArquivo.'.pdf');
    }

    /**
     * Processa os dados brutos em estrutura hierárquica conforme especificação
     * Níveis: Prestador -> Tipo de Financiamento -> Grupo -> Sub-grupo -> Forma -> Detalhe
     */
    private function processarDadosHierarquicos($dados)
    {
        $resultado = [];

        foreach ($dados as $registro) {
            $prestadorCodigo = $registro->prestador_codigo;
            $prestadorNome = $registro->prestador_nome;
            $tipoFinanciamento = $registro->tipo_financiamento;
            $grupoCodigo = $registro->grupo_codigo;
            $grupoDescricao = $registro->grupo_descricao ?: "Grupo $grupoCodigo";
            $subgrupoCodigo = $registro->subgrupo_codigo;
            $subgrupoDescricao = $registro->subgrupo_descricao ?: "Sub-grupo $subgrupoCodigo";
            $formaCodigo = $registro->forma_codigo;
            $formaDescricao = $registro->forma_descricao ?: "Forma $formaCodigo";

            // Nível 1: Prestador
            if (! isset($resultado[$prestadorCodigo])) {
                $resultado[$prestadorCodigo] = [
                    'codigo' => $prestadorCodigo,
                    'nome' => $prestadorNome,
                    'total_quantidade_apresentada' => 0,
                    'total_valor_apresentado' => 0,
                    'total_quantidade_aprovada' => 0,
                    'total_valor_aprovado' => 0,
                    'tipos_financiamento' => [],
                ];
            }

            // Nível 2: Tipo de Financiamento
            if (! isset($resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento])) {
                $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento] = [
                    'codigo' => $tipoFinanciamento,
                    'descricao' => $this->traduzirTipoFinanciamento($tipoFinanciamento),
                    'total_quantidade_apresentada' => 0,
                    'total_valor_apresentado' => 0,
                    'total_quantidade_aprovada' => 0,
                    'total_valor_aprovado' => 0,
                    'grupos' => [],
                ];
            }

            // Nível 3: Grupo
            if (! isset($resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo])) {
                $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo] = [
                    'codigo' => $grupoCodigo,
                    'descricao' => $grupoDescricao,
                    'total_quantidade_apresentada' => 0,
                    'total_valor_apresentado' => 0,
                    'total_quantidade_aprovada' => 0,
                    'total_valor_aprovado' => 0,
                    'subgrupos' => [],
                ];
            }

            // Nível 4: Sub-grupo
            if (! isset($resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['subgrupos'][$subgrupoCodigo])) {
                $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['subgrupos'][$subgrupoCodigo] = [
                    'codigo' => $subgrupoCodigo,
                    'descricao' => $subgrupoDescricao,
                    'total_quantidade_apresentada' => 0,
                    'total_valor_apresentado' => 0,
                    'total_quantidade_aprovada' => 0,
                    'total_valor_aprovado' => 0,
                    'formas' => [],
                ];
            }

            // Nível 5: Forma de Organização
            if (! isset($resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['subgrupos'][$subgrupoCodigo]['formas'][$formaCodigo])) {
                $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['subgrupos'][$subgrupoCodigo]['formas'][$formaCodigo] = [
                    'codigo' => $formaCodigo,
                    'descricao' => $formaDescricao,
                    'total_quantidade_apresentada' => 0,
                    'total_valor_apresentado' => 0,
                    'total_quantidade_aprovada' => 0,
                    'total_valor_aprovado' => 0,
                    'procedimentos' => [],
                ];
            }

            // Nível 6: Detalhe (Procedimento)
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['subgrupos'][$subgrupoCodigo]['formas'][$formaCodigo]['procedimentos'][] = [
                'codigo' => $registro->procedimento_codigo,
                'nome' => $registro->procedimento_nome,
                'valor_unitario' => $registro->valor_unitario,
                'quantidade_apresentada' => $registro->quantidade_apresentada,
                'valor_apresentado' => $registro->valor_apresentado,
                'quantidade_aprovada' => $registro->quantidade_aprovada,
                'valor_aprovado' => $registro->valor_aprovado,
            ];

            // Somar totais em todos os níveis
            $quantidadeApresentada = $registro->quantidade_apresentada ?? 0;
            $valorApresentado = $registro->valor_apresentado ?? 0;
            $quantidadeAprovada = $registro->quantidade_aprovada ?? 0;
            $valorAprovado = $registro->valor_aprovado ?? 0;

            // Total do Prestador
            $resultado[$prestadorCodigo]['total_quantidade_apresentada'] += $quantidadeApresentada;
            $resultado[$prestadorCodigo]['total_valor_apresentado'] += $valorApresentado;
            $resultado[$prestadorCodigo]['total_quantidade_aprovada'] += $quantidadeAprovada;
            $resultado[$prestadorCodigo]['total_valor_aprovado'] += $valorAprovado;

            // Total do Tipo de Financiamento
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['total_quantidade_apresentada'] += $quantidadeApresentada;
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['total_valor_apresentado'] += $valorApresentado;
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['total_quantidade_aprovada'] += $quantidadeAprovada;
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['total_valor_aprovado'] += $valorAprovado;

            // Total do Grupo
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['total_quantidade_apresentada'] += $quantidadeApresentada;
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['total_valor_apresentado'] += $valorApresentado;
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['total_quantidade_aprovada'] += $quantidadeAprovada;
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['total_valor_aprovado'] += $valorAprovado;

            // Total do Sub-grupo
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['subgrupos'][$subgrupoCodigo]['total_quantidade_apresentada'] += $quantidadeApresentada;
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['subgrupos'][$subgrupoCodigo]['total_valor_apresentado'] += $valorApresentado;
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['subgrupos'][$subgrupoCodigo]['total_quantidade_aprovada'] += $quantidadeAprovada;
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['subgrupos'][$subgrupoCodigo]['total_valor_aprovado'] += $valorAprovado;

            // Total da Forma de Organização
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['subgrupos'][$subgrupoCodigo]['formas'][$formaCodigo]['total_quantidade_apresentada'] += $quantidadeApresentada;
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['subgrupos'][$subgrupoCodigo]['formas'][$formaCodigo]['total_valor_apresentado'] += $valorApresentado;
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['subgrupos'][$subgrupoCodigo]['formas'][$formaCodigo]['total_quantidade_aprovada'] += $quantidadeAprovada;
            $resultado[$prestadorCodigo]['tipos_financiamento'][$tipoFinanciamento]['grupos'][$grupoCodigo]['subgrupos'][$subgrupoCodigo]['formas'][$formaCodigo]['total_valor_aprovado'] += $valorAprovado;
        }

        return $resultado;
    }

    /**
     * Traduz códigos de tipo de financiamento para descrições legíveis
     */
    private function traduzirTipoFinanciamento($codigo)
    {
        $traducoes = [
            '01' => 'Atenção Básica (AB)',
            '02' => 'Média e Alta Complexidade Ambulatorial (MAC)',
            '03' => 'Média e Alta Complexidade Hospitalar (MAC)',
            '04' => 'Vigilância em Saúde (VS)',
            '05' => 'Assistência Farmacêutica (AF)',
            '06' => 'Média e Alta Complexidade (MAC)',
            '07' => 'Gestão do SUS (GS)',
            '08' => 'Investimentos na Rede de Serviços (IRS)',
            '09' => 'Outros',
            '10' => 'Atenção Básica - PAB Fixo',
            '11' => 'Atenção Básica - PAB Variável',
            '12' => 'Atenção Básica - PAB Estratégia Saúde da Família',
            '13' => 'Atenção Básica - PAB Agentes Comunitários de Saúde',
            '14' => 'Atenção Básica - PAB Agentes de Endemias',
            '15' => 'Atenção Básica - PAB Equipe de Saúde Bucal',
            '16' => 'Atenção Básica - PAB Equipe de Saúde Mental',
            '17' => 'Atenção Básica - PAB Equipe de Saúde da Família',
            '18' => 'Atenção Básica - PAB Equipe de Saúde Bucal',
            '19' => 'Atenção Básica - PAB Equipe de Saúde Mental',
            '20' => 'Atenção Básica - PAB Equipe de Saúde da Família',
        ];

        return $traducoes[$codigo] ?? "Tipo de Financiamento $codigo";
    }

    private function formatCompetencia($competencia)
    {
        if (strlen($competencia) === 6) {
            $ano = substr($competencia, 0, 4);
            $mes = substr($competencia, 4, 2);

            $meses = [
                '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
                '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
                '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
                '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro',
            ];

            return $meses[$mes].'/'.$ano;
        }

        return $competencia;
    }
}
