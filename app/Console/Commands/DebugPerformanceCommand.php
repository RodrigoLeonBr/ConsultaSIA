<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class DebugPerformanceCommand extends Command
{
    protected $signature = 'debug:performance {--explain : Inclui EXPLAIN da query de amostra em s_prd}';

    protected $description = 'Diagnóstico rápido de performance: runtime, cache, banco e índices core';

    /** @var list<string> */
    private const CORE_TABLES = [
        's_prd',
        's_apa',
        's_bpi',
        's_pap',
        's_rub',
        'prestador',
        'procedimento',
        'cbo',
        'forma',
        'cismetro',
    ];

    /** @var list<string> */
    private const SPRD_REQUIRED_INDEXES = [
        'idx_composite',
        'idx_prd_cmp',
        'idx_prd_uid',
        'idx_prd_pa',
    ];

    public function handle(): int
    {
        $this->line('');
        $this->info('ConsultaProd — debug:performance');
        $this->line(str_repeat('─', 50));

        $this->reportRuntime();
        $this->reportCache();
        $this->reportDatabase();

        if ($this->option('explain')) {
            $this->reportExplain();
        }

        $this->line('');
        $this->comment('Dica: use --explain para EXPLAIN da query de amostra em s_prd.');

        return self::SUCCESS;
    }

    private function reportRuntime(): void
    {
        $this->newLine();
        $this->info('Runtime');

        $memoryLimit = ini_get('memory_limit') ?: 'n/d';
        $maxExecution = ini_get('max_execution_time') ?: '0';

        $this->table(
            ['Item', 'Valor'],
            [
                ['PHP', PHP_VERSION],
                ['Laravel', app()->version()],
                ['APP_ENV', config('app.env')],
                ['memory_limit', $memoryLimit],
                ['max_execution_time', $maxExecution.'s'],
                ['opcache', $this->opcacheStatus()],
            ],
        );
    }

    private function reportCache(): void
    {
        $this->newLine();
        $this->info('Cache / bootstrap');

        $this->table(
            ['Item', 'Status'],
            [
                ['config:cache', file_exists(base_path('bootstrap/cache/config.php')) ? 'ativo' : 'inativo'],
                ['route:cache', file_exists(base_path('bootstrap/cache/routes-v7.php')) ? 'ativo' : 'inativo'],
                ['view:cache', is_dir(storage_path('framework/views')) && count(glob(storage_path('framework/views/*.php'))) > 0 ? 'compiladas' : 'vazio'],
                ['CACHE_STORE', config('cache.default')],
            ],
        );
    }

    private function reportDatabase(): void
    {
        $this->newLine();
        $this->info('Banco de dados');

        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $pingMs = $this->elapsedMs($start);
            $this->line("Conexão: OK ({$pingMs} ms) — ".config('database.default').' @ '.config('database.connections.'.config('database.default').'.database'));
        } catch (Throwable $exception) {
            $this->error('Conexão: FALHOU — '.$exception->getMessage());

            return;
        }

        $rows = [];

        foreach (self::CORE_TABLES as $table) {
            if (! $this->tableExists($table)) {
                $rows[] = [$table, 'ausente', '-', '-'];

                continue;
            }

            $meta = $this->tableMeta($table);
            $rows[] = [
                $table,
                'ok',
                $meta['engine'] ?? '-',
                $this->formatRows($meta['rows']),
            ];
        }

        $this->table(['Tabela', 'Status', 'Engine', 'Linhas (est.)'], $rows);

        if ($this->tableExists('s_prd')) {
            $this->reportSprdChecks();
        }
    }

    private function reportSprdChecks(): void
    {
        $this->newLine();
        $this->info('s_prd — competência e índices');

        try {
            $latestCmp = DB::table('s_prd')->max('prd_cmp');

            if ($latestCmp === null) {
                $this->warn('s_prd vazia — sem competência para medir.');

                return;
            }

            $this->line("Última competência: {$latestCmp}");

            $start = microtime(true);
            $count = DB::table('s_prd')->where('prd_cmp', $latestCmp)->count();
            $countMs = $this->elapsedMs($start);
            $this->line("COUNT com prd_cmp={$latestCmp}: {$this->formatRows($count)} linhas ({$countMs} ms)");

            $start = microtime(true);
            DB::selectOne(
                'SELECT SUM(CAST(PRD_QT_P AS UNSIGNED)) AS qty FROM s_prd WHERE prd_cmp = ?',
                [$latestCmp],
            );
            $aggMs = $this->elapsedMs($start);
            $this->line("SUM(CAST(PRD_QT_P)) na competência: {$aggMs} ms");

            $indexes = collect(DB::select('SHOW INDEX FROM s_prd'))
                ->pluck('Key_name')
                ->unique()
                ->values()
                ->all();

            $missing = array_values(array_diff(self::SPRD_REQUIRED_INDEXES, $indexes));

            if ($missing === []) {
                $this->line('<fg=green>Índices obrigatórios: OK</>');
            } else {
                $this->warn('Índices ausentes: '.implode(', ', $missing));
            }
        } catch (Throwable $exception) {
            $this->warn('Checagem s_prd indisponível: '.$exception->getMessage());
        }
    }

    private function reportExplain(): void
    {
        if (! $this->tableExists('s_prd')) {
            $this->warn('EXPLAIN ignorado — tabela s_prd ausente.');

            return;
        }

        $this->newLine();
        $this->info('EXPLAIN — query de amostra (s_prd filtrada por competência)');

        try {
            $latestCmp = DB::table('s_prd')->max('prd_cmp');

            if ($latestCmp === null) {
                $this->warn('s_prd vazia — sem competência para EXPLAIN.');

                return;
            }

            $plan = DB::select(
                'EXPLAIN SELECT prd_cmp, SUM(CAST(PRD_QT_P AS UNSIGNED)) AS qty
         FROM s_prd
         WHERE prd_cmp = ?
         GROUP BY prd_cmp',
                [$latestCmp],
            );

            $this->table(
                ['id', 'select_type', 'table', 'type', 'possible_keys', 'key', 'rows', 'Extra'],
                collect($plan)->map(fn ($row) => [
                    $row->id ?? '-',
                    $row->select_type ?? '-',
                    $row->table ?? '-',
                    $row->type ?? '-',
                    $row->possible_keys ?? '-',
                    $row->key ?? '-',
                    $row->rows ?? '-',
                    $row->Extra ?? '-',
                ])->all(),
            );

            $accessType = $plan[0]->type ?? null;

            if ($accessType === 'ALL') {
                $this->error('ALERTA: type=ALL — full table scan na amostra. Verifique filtro de competência e índices.');
            } elseif (in_array($accessType, ['index', 'range', 'ref', 'eq_ref', 'const'], true)) {
                $this->line('<fg=green>Plano aceitável: type='.$accessType.'</>');
            }
        } catch (Throwable $exception) {
            $this->warn('EXPLAIN indisponível: '.$exception->getMessage());
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                $result = DB::selectOne(
                    "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?",
                    [$table],
                );

                return $result !== null;
            }

            $database = DB::connection()->getDatabaseName();
            $result = DB::selectOne(
                'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [$database, $table],
            );

            return $result !== null;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array{rows: int|null, engine: string|null}
     */
    private function tableMeta(string $table): array
    {
        try {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                $count = DB::table($table)->count();

                return ['rows' => $count, 'engine' => 'sqlite'];
            }

            $database = DB::connection()->getDatabaseName();
            $row = DB::selectOne(
                'SELECT TABLE_ROWS AS rows_est, ENGINE AS engine
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                [$database, $table],
            );

            return [
                'rows' => isset($row->rows_est) ? (int) $row->rows_est : null,
                'engine' => $row->engine ?? null,
            ];
        } catch (Throwable) {
            return ['rows' => null, 'engine' => null];
        }
    }

    private function opcacheStatus(): string
    {
        if (! function_exists('opcache_get_status')) {
            return 'indisponível';
        }

        $status = opcache_get_status(false);

        if ($status === false) {
            return 'desabilitado';
        }

        return ($status['opcache_enabled'] ?? false) ? 'habilitado' : 'desabilitado';
    }

    private function elapsedMs(float $start): string
    {
        return number_format((microtime(true) - $start) * 1000, 1);
    }

    private function formatRows(int|string|null $rows): string
    {
        if ($rows === null) {
            return '-';
        }

        return number_format((int) $rows, 0, ',', '.');
    }
}
