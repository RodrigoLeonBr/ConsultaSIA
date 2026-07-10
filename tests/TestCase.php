<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private const PRODUCTION_DATABASE = 'producao';

    protected function setUp(): void
    {
        parent::setUp();

        if (! app()->environment('testing')) {
            return;
        }

        $database = (string) config('database.connections.'.config('database.default').'.database');

        if ($database === self::PRODUCTION_DATABASE) {
            $this->fail(
                'Testes bloqueados no banco "'.self::PRODUCTION_DATABASE.'". '
                .'Use DB_DATABASE=producao_test (phpunit.xml ou .env.testing).'
            );
        }
    }
}
