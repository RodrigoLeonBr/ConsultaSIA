@extends('layouts.modern')

@section('title', 'Dashboard - Sistema ConsultaProd')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600 mt-1">Visão geral do sistema ConsultaProd</p>
    </div>
    <div class="flex items-center space-x-4">
        <div class="text-right">
            <p class="text-sm text-gray-500">Última atualização</p>
            <p class="text-sm font-medium text-gray-900">{{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <button onclick="location.reload()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
        </button>
    </div>
</div>
@endsection

@section('content')
<!-- Welcome Banner -->
<div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 rounded-2xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold mb-2">Bem-vindo ao ConsultaProd</h2>
            <p class="text-blue-100 mb-4">Sistema de Gerenciamento e Relatórios Dinâmicos para Unidades de Saúde</p>
            <div class="flex items-center space-x-6 text-sm">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span>{{ Auth::user()->name ?? 'Usuário' }}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <span>{{ ucfirst(Auth::user()->role ?? 'Usuário') }}</span>
                </div>
            </div>
        </div>
        <div class="hidden md:block">
            <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <x-kpi-card 
        title="Prestadores Ativos" 
        :value="$stats['prestadores']" 
        :change="12.5" 
        changeType="positive"
        icon="users"
        color="blue"
        :sparkline="[45, 52, 48, 61, 55, 67, 72, 68, 75, 82, 78, 85]"
        format="number" />
    
    <x-kpi-card 
        title="Procedimentos" 
        :value="$stats['procedimentos']" 
        :change="8.2" 
        changeType="positive"
        icon="document"
        color="green"
        :sparkline="[120, 135, 128, 145, 152, 148, 165, 172, 168, 185, 192, 198]"
        format="number" />
    
    <x-kpi-card 
        title="CBO Cadastrados" 
        :value="$stats['cbo_count']" 
        :change="2.1" 
        changeType="positive"
        icon="chart"
        color="yellow"
        :sparkline="[2800, 2810, 2805, 2815, 2820, 2818, 2825, 2830, 2828, 2835, 2840, 2842]"
        format="number" />
    
    <x-kpi-card 
        title="Taxa de Aprovação APAC" 
        :value="87.5" 
        :change="3.2" 
        changeType="positive"
        icon="check"
        color="purple"
        :sparkline="[82, 84, 83, 86, 85, 87, 88, 86, 89, 90, 88, 87.5]"
        format="percentage" />
</div>

<!-- Additional KPI Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <x-kpi-card 
        title="Registros Pendentes" 
        :value="23" 
        :change="-15.2" 
        changeType="negative"
        icon="clock"
        color="red"
        :sparkline="[45, 42, 38, 35, 32, 28, 25, 23, 20, 18, 22, 23]"
        format="number" />
    
    <x-kpi-card 
        title="Valor Total Produção" 
        :value="$stats['financiamentos']['total_ano'] ?? 0" 
        :change="18.7" 
        changeType="positive"
        icon="money"
        color="indigo"
        :sparkline="[45000, 48000, 52000, 55000, 58000, 62000, 65000, 68000, 72000, 75000, 78000, 82000]"
        format="currency" />
    
    <x-kpi-card 
        title="Último Período" 
        :value="$stats['financiamentos']['ultimo_periodo'] ?? 0" 
        :change="5.8" 
        changeType="positive"
        icon="chart"
        color="green"
        :sparkline="[6800, 7200, 7500, 7800, 8200, 8500, 8800, 9200, 9500, 9800, 10200, 10500]"
        format="currency" />
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Production Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Produção dos Últimos 30 Dias</h3>
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                <span class="text-sm text-gray-600">Procedimentos</span>
            </div>
        </div>
        <div class="h-64 flex items-end justify-between px-4 py-4">
            <!-- Simple bar chart using CSS -->
            <div class="flex items-end space-x-2 h-full">
                @for($i = 0; $i < 7; $i++)
                    <div class="bg-blue-500 rounded-t" style="height: {{ rand(20, 100) }}%; width: 20px;" title="Dia {{ $i + 1 }}: {{ rand(50, 200) }} procedimentos"></div>
                @endfor
            </div>
        </div>
    </div>
    
    <!-- Distribution Chart -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Distribuição por Prestador</h3>
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                <span class="text-sm text-gray-600">Top 5</span>
            </div>
        </div>
        <div class="h-64 flex items-center justify-center">
            <!-- Simple pie chart representation using CSS -->
            <div class="relative w-32 h-32">
                <div class="absolute inset-0 rounded-full border-8 border-green-500" style="clip-path: polygon(50% 50%, 50% 0%, 100% 0%, 100% 100%, 0% 100%, 0% 0%);"></div>
                <div class="absolute inset-0 rounded-full border-8 border-blue-500" style="clip-path: polygon(50% 50%, 100% 0%, 100% 50%);"></div>
                <div class="absolute inset-0 rounded-full border-8 border-yellow-500" style="clip-path: polygon(50% 50%, 100% 50%, 100% 100%);"></div>
                <div class="absolute inset-0 rounded-full border-8 border-red-500" style="clip-path: polygon(50% 50%, 0% 100%, 0% 50%);"></div>
                <div class="absolute inset-0 rounded-full border-8 border-purple-500" style="clip-path: polygon(50% 50%, 0% 50%, 0% 0%);"></div>
            </div>
            <div class="ml-8 space-y-2">
                <div class="flex items-center space-x-2"><div class="w-3 h-3 bg-green-500 rounded-full"></div><span class="text-sm">Hospital Central (65%)</span></div>
                <div class="flex items-center space-x-2"><div class="w-3 h-3 bg-blue-500 rounded-full"></div><span class="text-sm">UBS Norte (18%)</span></div>
                <div class="flex items-center space-x-2"><div class="w-3 h-3 bg-yellow-500 rounded-full"></div><span class="text-sm">UBS Sul (12%)</span></div>
                <div class="flex items-center space-x-2"><div class="w-3 h-3 bg-red-500 rounded-full"></div><span class="text-sm">UBS Leste (8%)</span></div>
                <div class="flex items-center space-x-2"><div class="w-3 h-3 bg-purple-500 rounded-full"></div><span class="text-sm">UBS Oeste (5%)</span></div>
            </div>
        </div>
    </div>
</div>

<!-- Top Procedures Chart -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Top 5 Procedimentos</h3>
        <a href="{{ route('procedimento.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
            Ver todos →
        </a>
    </div>
        <div class="h-80 space-y-4">
            <!-- Simple horizontal bar chart -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Consulta Médica</span>
                    <span class="text-sm text-gray-600">1,250</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: 100%"></div>
                </div>
            </div>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Exame Laboratorial</span>
                    <span class="text-sm text-gray-600">980</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: 78%"></div>
                </div>
            </div>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Raio-X</span>
                    <span class="text-sm text-gray-600">850</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-yellow-500 h-2 rounded-full" style="width: 68%"></div>
                </div>
            </div>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Ultrassom</span>
                    <span class="text-sm text-gray-600">720</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-red-500 h-2 rounded-full" style="width: 58%"></div>
                </div>
            </div>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Tomografia</span>
                    <span class="text-sm text-gray-600">650</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-purple-500 h-2 rounded-full" style="width: 52%"></div>
                </div>
            </div>
        </div>
</div>

<!-- Activity Timeline and Quick Actions -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Activity Timeline -->
    <div class="lg:col-span-1">
        <x-activity-timeline :activities="$recentActivities ?? []" />
    </div>
    
    <!-- Quick Actions -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Ações Rápidas</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @can('admin-access')
                    <x-action-card 
                        title="Painel Admin" 
                        description="Administração completa do sistema, usuários e configurações"
                        icon="settings"
                        color="red"
                        href="{{ route('admin.dashboard') }}"
                        badge="Admin" />
                    
                    <x-action-card 
                        title="Gerenciar Usuários" 
                        description="Controle de usuários, permissões e roles do sistema"
                        icon="users"
                        color="orange"
                        href="{{ route('admin.users') }}"
                        badge="Admin" />
                @endcan
                
                @can('operator-access')
                    <x-action-card 
                        title="Relatórios Produção" 
                        description="Relatórios dinâmicos e exportação de dados de produção"
                        icon="report"
                        color="purple"
                        href="{{ route('relatorios.index') }}"
                        badge="Relatórios" />
                    
                    <x-action-card 
                        title="Relatórios APAC/OCI" 
                        description="Relatórios específicos para APAC e OCI com filtros avançados"
                        icon="document"
                        color="green"
                        href="{{ route('relatorios.apac.index') }}"
                        badge="APAC" />
                    
                    <x-action-card 
                        title="Faturamento Prestador" 
                        description="Relatório analítico hierárquico por prestador"
                        icon="chart"
                        color="blue"
                        href="{{ route('faturamento-prestador.index') }}"
                        badge="Analítico" />
                    
                    <x-action-card 
                        title="Gerenciar CBO" 
                        description="Cadastro e consulta de códigos de ocupação"
                        icon="database"
                        color="yellow"
                        href="{{ route('cbo.index') }}"
                        badge="CBO" />
                @else
                    <x-action-card 
                        title="Acesso Limitado" 
                        description="Entre em contato com o administrador para obter permissões"
                        icon="shield"
                        color="gray"
                        href="#"
                        badge="Limitado" />
                @endcan
            </div>
        </div>
    </div>
</div>

<!-- System Info -->
<div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações do Sistema</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <div class="text-2xl font-bold text-gray-900">{{ app()->version() }}</div>
            <div class="text-sm text-gray-600">Laravel Framework</div>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <div class="text-2xl font-bold text-gray-900">{{ phpversion() }}</div>
            <div class="text-sm text-gray-600">PHP Version</div>
        </div>
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <div class="text-2xl font-bold text-gray-900">2.0</div>
            <div class="text-sm text-gray-600">ConsultaProd Version</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadRecentActivity();
});

// Load recent activity
async function loadRecentActivity() {
    try {
        const response = await fetch('{{ route("dashboard.activity") }}');
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Update activity timeline with real data
        updateActivityTimeline(data.recent_production || []);
    } catch (error) {
        console.error('Error loading recent activity:', error);
        showToast('Erro ao carregar atividade recente', 'error');
    }
}

function updateActivityTimeline(data) {
    // This would update the activity timeline component
    // For now, we'll just log the data
    console.log('Recent activity data:', data);
}
</script>
@endpush
