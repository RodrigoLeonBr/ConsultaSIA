<?php

namespace Tests\Feature;

use Tests\TestCase;

class DebugPerformanceCommandTest extends TestCase
{
    public function test_debug_performance_command_runs_successfully(): void
    {
        $this->artisan('debug:performance')
            ->assertSuccessful()
            ->expectsOutputToContain('ConsultaProd — debug:performance')
            ->expectsOutputToContain('Runtime')
            ->expectsOutputToContain('Banco de dados');
    }

    public function test_debug_performance_explain_option_runs_successfully(): void
    {
        $this->artisan('debug:performance --explain')
            ->assertSuccessful()
            ->expectsOutputToContain('Dica: use --explain');
    }
}
