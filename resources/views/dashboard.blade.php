<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard - Sistema ConsultaProd') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Welcome Card -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-white">
                    <h3 class="text-2xl font-bold mb-2">Bem-vindo ao ConsultaProd</h3>
                    <p class="text-blue-100">Sistema de Gerenciamento e Relatórios Dinâmicos para Unidades de Saúde</p>
                    <p class="text-blue-100 mt-2">Usuário: <strong>{{ Auth::user()->full_name }}</strong> ({{ ucfirst(Auth::user()->role) }})</p>
                </div>
            </div>

            <!-- System Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Prestadores Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['prestadores']) }}</div>
                                <p class="text-gray-600">Prestadores Ativos</p>
                                <a href="{{ route('prestador.index') }}" class="text-xs text-blue-600 hover:text-blue-800">Ver todos →</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Procedimentos Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['procedimentos']) }}</div>
                                <p class="text-gray-600">Procedimentos</p>
                                <a href="{{ route('procedimento.index') }}" class="text-xs text-green-600 hover:text-green-800">Ver todos →</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- CBO Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['cbo_count']) }}</div>
                                <p class="text-gray-600">CBO Cadastrados</p>
                                <a href="{{ route('cbo.index') }}" class="text-xs text-yellow-600 hover:text-yellow-800">Ver todos →</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Financiamentos Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-gray-900">{{ $stats['financiamentos']['ultimo_periodo'] }}</div>
                                <p class="text-gray-600 text-sm">Último Período</p>
                                <div class="text-sm font-semibold text-purple-600">{{ $stats['financiamentos']['total_ano'] }}</div>
                                <p class="text-gray-500 text-xs">Total do Ano</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Atividade Recente</h3>
                    <div id="recent-activity">
                        <div class="text-center py-4">
                            <div class="inline-flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-gray-500">Carregando atividade recente...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @can('admin-access')
                <!-- Admin Panel -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Administração</h3>
                        <div class="space-y-3">
                            <a href="{{ route('admin.dashboard') }}" class="block p-3 bg-red-50 hover:bg-red-100 rounded-lg transition duration-150">
                                <div class="font-medium text-red-800">Painel Admin</div>
                                <div class="text-sm text-red-600">Administração completa do sistema</div>
                            </a>
                            <a href="{{ route('admin.users') }}" class="block p-3 bg-orange-50 hover:bg-orange-100 rounded-lg transition duration-150">
                                <div class="font-medium text-orange-800">Gerenciar Usuários</div>
                                <div class="text-sm text-orange-600">Controle de usuários e permissões</div>
                            </a>
                        </div>
                    </div>
                </div>
                @endcan

                @can('operator-access')
                <!-- Operator/General Access -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Operações</h3>
                        <div class="space-y-3">
                            @if(!Auth::user()->isAdmin())
                                <a href="{{ route('operator.dashboard') }}" class="block p-3 bg-purple-50 hover:bg-purple-100 rounded-lg transition duration-150">
                                    <div class="font-medium text-purple-800">Painel Operador</div>
                                    <div class="text-sm text-purple-600">Acesso às operações do sistema</div>
                                </a>
                            @endif
                            <div class="p-3 bg-blue-50 rounded-lg">
                                <div class="font-medium text-blue-800">Gerenciar CBO</div>
                                <div class="text-sm text-blue-600">Cadastro e consulta de ocupações</div>
                            </div>
                            <div class="p-3 bg-green-50 rounded-lg">
                                <div class="font-medium text-green-800">Gerenciar Prestadores</div>
                                <div class="text-sm text-green-600">Cadastro de unidades prestadoras</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Relatórios</h3>
                        <div class="space-y-3">
                            <a href="{{ route('relatorios.index') }}" class="block p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                                <div class="font-medium text-purple-800">Gerar Relatórios</div>
                                <div class="text-sm text-purple-600">Relatórios dinâmicos e exportação</div>
                            </a>
                            <a href="{{ route('faturamento-prestador.index') }}" class="block p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                                <div class="font-medium text-blue-800">Faturamento por Prestador</div>
                                <div class="text-sm text-blue-600">Relatório analítico hierárquico</div>
                            </a>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-red-900 mb-4">Acesso Limitado</h3>
                        <div class="space-y-3">
                            <div class="p-3 bg-red-50 rounded-lg">
                                <div class="font-medium text-red-800">Sem Permissões</div>
                                <div class="text-sm text-red-600">Entre em contato com o administrador</div>
                            </div>
                        </div>
                    </div>
                </div>
                @endcan

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Sistema</h3>
                        <div class="space-y-3">
                            <div class="text-sm text-gray-600">
                                <strong>Versão:</strong> 1.0.0<br>
                                <strong>Laravel:</strong> {{ app()->version() }}<br>
                                <strong>PHP:</strong> {{ phpversion() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load recent activity
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentActivity();
        });

        async function loadRecentActivity() {
            try {
                const response = await fetch('{{ route("dashboard.activity") }}');
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                renderRecentActivity(data.recent_production);
            } catch (error) {
                console.error('Error loading recent activity:', error);
                document.getElementById('recent-activity').innerHTML = `
                    <div class="text-center py-4 text-red-600">
                        <p>Erro ao carregar atividade recente</p>
                        <p class="text-sm">${error.message}</p>
                    </div>
                `;
            }
        }

        function renderRecentActivity(data) {
            const container = document.getElementById('recent-activity');
            
            if (!data || data.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-4 text-gray-500">
                        <p>Nenhuma atividade recente encontrada</p>
                    </div>
                `;
                return;
            }
            
            const rows = data.map(item => {
                const periodo = item.prd_cmp;
                const periodoFormatado = periodo ? `${periodo.substring(0,4)}-${periodo.substring(4,6)}` : 'N/A';
                const quantidade = parseInt(item.total_quantidade || 0);
                const valor = parseFloat(item.total_valor || 0);
                
                return `
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">${item.re_cnome || 'N/A'}</div>
                            <div class="text-sm text-gray-500">Período: ${periodoFormatado}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-medium text-gray-900">${quantidade.toLocaleString()} proc.</div>
                            <div class="text-sm text-green-600">R$ ${valor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                        </div>
                    </div>
                `;
            }).join('');
            
            container.innerHTML = `
                <div class="space-y-0">
                    ${rows}
                </div>
                <div class="mt-4 text-center">
                    <a href="{{ route('relatorios.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Ver relatórios completos →
                    </a>
                </div>
            `;
        }
    </script>
</x-app-layout>
