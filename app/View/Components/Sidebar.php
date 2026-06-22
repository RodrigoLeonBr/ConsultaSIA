<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Sidebar extends Component
{
    public string $active;

    /** @var array<int, array{label: ?string, items: array<int, array{id: string, label: string, route: string, icon: string}>}> */
    public array $sections;

    public function __construct(?string $active = null)
    {
        $this->active = $active ?? self::resolveActiveRoute();
        $this->sections = self::menuSections();
    }

    public static function resolveActiveRoute(): string
    {
        if (request()->routeIs('admin.*')) {
            return 'admin';
        }

        if (request()->routeIs('cbo.*')) {
            return 'cbo';
        }

        if (request()->routeIs('prestador.*')) {
            return 'prestador';
        }

        if (request()->routeIs('procedimento.*')) {
            return 'procedimento';
        }

        if (request()->routeIs('cismetro.*')) {
            return 'cismetro';
        }

        if (request()->routeIs('srub.*')) {
            return 'financiamento';
        }

        if (request()->routeIs('relatorios.bpi.*')) {
            return 'bpi';
        }

        if (request()->routeIs('relatorios.apac.*')) {
            return 'apac';
        }

        if (request()->routeIs('faturamento-prestador.*')) {
            return 'faturamento';
        }

        if (request()->routeIs('aih.import*')) {
            return 'aih-import';
        }

        if (request()->routeIs('relatorios.aih-pa.*')) {
            return 'aih-pa';
        }

        if (request()->routeIs('relatorios.aih.*')) {
            return 'aih';
        }

        if (request()->routeIs('relatorios.*')) {
            return 'relatorios';
        }

        if (request()->routeIs('painel')) {
            return 'painel';
        }

        return 'dashboard';
    }

    /**
     * @return array<int, array{label: ?string, items: array<int, array{id: string, label: string, route: string, icon: string}>}>
     */
    public static function menuSections(): array
    {
        return [
            [
                'label' => 'Principal',
                'items' => [
                    ['id' => 'dashboard', 'label' => 'Início',  'route' => 'dashboard', 'icon' => 'dashboard'],
                    ['id' => 'painel',    'label' => 'Painel',  'route' => 'painel',     'icon' => 'dashboard'],
                ],
            ],
            [
                'label' => 'Cadastros',
                'items' => [
                    ['id' => 'cbo', 'label' => 'CBO', 'route' => 'cbo.index', 'icon' => 'cbo'],
                    ['id' => 'prestador', 'label' => 'Prestadores', 'route' => 'prestador.index', 'icon' => 'prestador'],
                    ['id' => 'procedimento', 'label' => 'Procedimentos', 'route' => 'procedimento.index', 'icon' => 'procedimento'],
                    ['id' => 'cismetro', 'label' => 'Cismetro', 'route' => 'cismetro.index', 'icon' => 'cismetro'],
                    ['id' => 'financiamento', 'label' => 'Financiamentos', 'route' => 'srub.index', 'icon' => 'financiamento'],
                ],
            ],
            [
                'label' => 'Relatórios',
                'items' => [
                    ['id' => 'relatorios', 'label' => 'Relatórios Produção', 'route' => 'relatorios.index', 'icon' => 'relatorios'],
                    ['id' => 'bpi', 'label' => 'Produção Individualizada', 'route' => 'relatorios.bpi.index', 'icon' => 'bpi'],
                    ['id' => 'apac', 'label' => 'Relatório de APAC', 'route' => 'relatorios.apac.index', 'icon' => 'apac'],
                    ['id' => 'faturamento', 'label' => 'Faturamento por Prestador', 'route' => 'faturamento-prestador.index', 'icon' => 'faturamento'],
                ],
            ],
            [
                'label' => 'Internações (SIH)',
                'items' => [
                    ['id' => 'aih-import', 'label' => 'Importar AIH',         'route' => 'aih.import',              'icon' => 'aih-import'],
                    ['id' => 'aih',        'label' => 'Internações AIH',       'route' => 'relatorios.aih.index',    'icon' => 'aih'],
                    ['id' => 'aih-pa',     'label' => 'Procedimentos AIH',     'route' => 'relatorios.aih-pa.index', 'icon' => 'aih-pa'],
                ],
            ],
            [
                'label' => 'Sistema',
                'items' => [
                    ['id' => 'admin', 'label' => 'Admin', 'route' => 'admin.dashboard', 'icon' => 'admin'],
                ],
            ],
        ];
    }

    public function render()
    {
        return view('components.sidebar');
    }
}
