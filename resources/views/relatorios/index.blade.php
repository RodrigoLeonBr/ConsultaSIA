@extends('layouts.modern')

@section('title', 'Gerador de Relatórios Dinâmicos')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Relatórios de Produção</h1>
        <p class="text-gray-600 mt-1">Gerador de relatórios dinâmicos e exportação de dados</p>
    </div>
</div>
@endsection

@section('content')
    <style>
        /* Garantir que o botão Adicionar Filtro seja sempre visível */
        #add-filter {
            min-width: 140px;
            white-space: nowrap;

            /* ADICIONE ESTES DOIS CSS PARA FORÇAR A EXIBIÇÃO */
            height: 32px !important; 
            line-height: 20px !important;
        }
        
        /* Melhorar layout responsivo do painel de filtros */
        @media (max-width: 1024px) {
            .lg\\:col-span-2 {
                margin-top: 1rem;
            }
        }
    </style>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Info Panel -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-blue-800">Relatórios de Produção Hospitalar</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Este sistema gera relatórios da <strong>produção hospitalar</strong> (tabela s_prd) com 5.988.427 registros.</p>
                            <p class="mt-1">Para relatórios de <strong>APAC/OCI</strong>, acesse: 
                                <a href="{{ route('relatorios.apac.index') }}" class="font-medium text-blue-800 hover:text-blue-900 underline">
                                    Relatórios APAC/OCI →
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Builder Interface -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3
                     gap-6">
                        
                        <!-- Field Selection Panel -->
                        <div class="lg:col-span-1">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Seleção de Campos</h3>
                            <div class="space-y-2 max-h-96 overflow-y-auto border rounded-lg p-4">
                                <div id="field-checkboxes">
                                    <!-- Fields will be loaded here via JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Filters Panel -->
                        <div class="lg:col-span-2">
                            <div class="flex justify-between items-center mb-4 gap-4">
                                <h3 class="text-lg font-medium text-gray-900 flex-grow">Filtros Avançados
                                <button id="add-filter" 
                                        style="background-color: #3b82f6 !important; color: white !important; padding: 0.25rem 0.75rem !important; border-radius: 0.25rem !important; font-size: 0.875rem !important; display: inline-block !important; visibility: visible !important; opacity: 1 !important; min-width: 140px !important; white-space: nowrap !important; height: 32px !important; line-height: 20px !important; border: none !important; cursor: pointer !important;"
                                        onmouseover="this.style.backgroundColor='#2563eb'"
                                        onmouseout="this.style.backgroundColor='#3b82f6'">
                                    + Adicionar Filtro
                                </button>
                                </h3>
                            </div>
                            
                            <div id="filters-container" class="space-y-3 min-h-32 border rounded-lg p-4">
                                <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                                    <label class="flex items-center">
                                        <input type="checkbox" id="sus-paulista-filter" class="mr-2 rounded border-gray-300">
                                        <span class="text-sm font-medium text-emerald-800">
                                            Somente procedimentos com Tabela SUS Paulista (SIA)
                                        </span>
                                    </label>
                                    <p class="text-xs text-emerald-600 mt-1">Oculta linhas sem cadastro vigente na tabela SUS Paulista para a competência.</p>
                                </div>
                                <p class="text-gray-500 text-sm" id="no-filters-message">Nenhum filtro adicionado. Clique em "Adicionar Filtro" para começar.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Visualization Controls -->
                    <div class="mt-6 p-4 bg-gray-50 rounded-lg" id="visualization-controls" style="display: none;">
                        <h4 class="text-md font-medium text-gray-900 mb-3 flex items-center">
                            Tipo de Visualização
                            <button type="button" class="ml-2 text-gray-400 hover:text-gray-600" onclick="toggleVisualizationHelp()">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </h4>
                        <div class="flex flex-col sm:flex-row sm:space-x-6 space-y-2 sm:space-y-0">
                            <label class="inline-flex items-center cursor-pointer group">
                                <input type="radio" name="view_type" value="list" checked class="form-radio text-blue-600">
                                <span class="ml-2 text-sm text-gray-700 group-hover:text-gray-900">Lista Simples</span>
                                <span class="ml-1 text-xs text-gray-400 hidden sm:inline">(tradicional)</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer group">
                                <input type="radio" name="view_type" value="matrix" class="form-radio text-blue-600">
                                <span class="ml-2 text-sm text-gray-700 group-hover:text-gray-900">Matriz por Competência/Movimento</span>
                                <span class="ml-1 text-xs text-gray-400 hidden sm:inline">(pivot table)</span>
                            </label>
                        </div>
                        <div id="visualization-help" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg" style="display: none;">
                            <div class="text-xs text-blue-800">
                                <p class="mb-2"><strong>Lista Simples:</strong> Exibe dados em formato de tabela tradicional, uma linha por registro.</p>
                                <p><strong>Matriz por Competência/Movimento:</strong> Transforma períodos (competência ou movimento) em colunas e outros campos em linhas. Selecione apenas um dos dois campos de data. Ideal para:</p>
                                <ul class="list-disc list-inside mt-1 ml-2">
                                    <li>Análises temporais e comparativas</li>
                                    <li>Visualizar tendências por período</li>
                                    <li>Comparar valores entre diferentes meses</li>
                                </ul>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <strong>Dica:</strong> A matriz funciona melhor com filtros de competência ou movimento para limitar o período.
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 flex flex-wrap gap-3">
                        <button id="generate-report" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            🔍 Gerar Relatório
                        </button>
                        <button id="cancel-search" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 hidden">
                            ❌ Cancelar Pesquisa
                        </button>
                        <button id="export-excel" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            📊 Exportar Excel
                        </button>
                        <button id="export-pdf" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            📄 Exportar PDF
                        </button>
                        <button id="export-csv" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                            📋 Exportar CSV
                        </button>
                        <a href="{{ route('relatorios.test-excel') }}" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 inline-block">
                            🧪 Teste Excel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Results Panel -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Resultados</h3>
                    
                    <!-- Loading Indicator -->
                    <div id="loading-indicator" class="hidden text-center py-8">
                        <div class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-gray-600">Processando relatório...</span>
                        </div>
                    </div>
                    
                    <div id="results-container">
                        <p class="text-gray-500 text-center py-8">Configure os campos e filtros acima e clique em "Gerar Relatório" para visualizar os dados.</p>
                    </div>
                </div>
            </div>

            <!-- SQL Debug Panel -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" id="sql-panel" style="display: none;">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">SQL Gerado</h3>
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <pre id="sql-display" class="text-sm text-gray-800 whitespace-pre-wrap"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal Template -->
    <div id="filter-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Configurar Filtro</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Campo</label>
                            <select id="filter-field" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Selecione um campo...</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Operador</label>
                            <select id="filter-operator" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Selecione um operador...</option>
                            </select>
                        </div>
                        
                        <div id="filter-value-container">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                            <input type="text" id="filter-value" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Digite o valor...">
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button id="cancel-filter" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button id="save-filter" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Adicionar Filtro
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Módulo JavaScript compartilhado -->
    <script src="{{ asset('js/relatorios-base.js') }}"></script>
    
    <script>
        // Report Builder JavaScript
        let availableFields = {};
        let selectedFields = [];
        let appliedFilters = [];
        let filterCounter = 0;
        const COMPETENCIA_FIELD = 'prd_cmp';
        const MOVIMENTO_FIELD = 'prd_mvm';
        const MATRIX_DATE_FIELDS = [COMPETENCIA_FIELD, MOVIMENTO_FIELD];
        
        // Aliases para funções do módulo compartilhado
        const showLoading = RelatoriosBase.showLoading;
        const hideLoading = RelatoriosBase.hideLoading;
        const showMatrixLoading = RelatoriosBase.showMatrixLoading;
        const showSQL = RelatoriosBase.showSQL;
        const loadLookupData = (field, search) => RelatoriosBase.loadLookupData(field, search, '{{ route("relatorios.lookup") }}');
        const renderResults = RelatoriosBase.renderResults;
        const renderListResults = RelatoriosBase.renderListResults;
        const renderMatrixResults = RelatoriosBase.renderMatrixResults;
        const cancelSearch = RelatoriosBase.cancelSearch;
        const showError = RelatoriosBase.showError;
        const showCancellation = RelatoriosBase.showCancellation;
        const handleFileDownload = RelatoriosBase.handleFileDownload;

        // Initialize the report builder
        document.addEventListener('DOMContentLoaded', function() {
            loadAvailableFields();
            setupEventListeners();
        });

        // Load available fields from server
        async function loadAvailableFields() {
            try {
                const url = '{{ route("relatorios.fields") }}';
                console.log('Loading fields from:', url);
                
                const response = await fetch(url);
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Fields loaded:', data);
                
                availableFields = data.fields;
                renderFieldCheckboxes();
                populateFilterFieldOptions();
            } catch (error) {
                console.error('Error loading fields:', error);
                // Show error message to user
                document.getElementById('field-checkboxes').innerHTML = 
                    '<p class="text-red-600 text-sm">Erro ao carregar campos. Verifique a conexão.</p>';
            }
        }

        // Render field checkboxes
        function renderFieldCheckboxes() {
            const container = document.getElementById('field-checkboxes');
            container.innerHTML = '';
            
            Object.keys(availableFields).forEach(fieldKey => {
                const field = availableFields[fieldKey];
                const div = document.createElement('div');
                div.className = 'flex items-center';
                div.innerHTML = `
                    <input type="checkbox" id="field-${fieldKey}" value="${fieldKey}" 
                           class="field-checkbox mr-2 rounded border-gray-300">
                    <label for="field-${fieldKey}" class="text-sm text-gray-700 cursor-pointer">
                        ${field.label}
                    </label>
                `;
                container.appendChild(div);
            });
        }

        // Setup event listeners
        function setupEventListeners() {
            // Field selection
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('field-checkbox')) {
                    enforceMatrixDateExclusion(e.target);
                    updateSelectedFields();
                }
                if (e.target.name === 'view_type' && e.target.value === 'matrix') {
                    enforceMatrixDateExclusionOnViewSwitch();
                    updateSelectedFields();
                }
            });

            // Filter management
            document.getElementById('add-filter').addEventListener('click', openFilterModal);
            document.getElementById('cancel-filter').addEventListener('click', closeFilterModal);
            document.getElementById('save-filter').addEventListener('click', saveFilter);
            
            // Field change in filter modal
            document.getElementById('filter-field').addEventListener('change', () => {
                updateFilterOperators();
                updateFilterValueInput();
            });
            
            // Report generation
            document.getElementById('generate-report').addEventListener('click', () => generateReport('html'));
            document.getElementById('cancel-search').addEventListener('click', cancelSearch);
            document.getElementById('export-excel').addEventListener('click', () => generateReport('excel'));
            document.getElementById('export-pdf').addEventListener('click', () => generateReport('pdf'));
            document.getElementById('export-csv').addEventListener('click', () => generateReport('csv'));
        }

        // Update selected fields array
        function updateSelectedFields() {
            selectedFields = Array.from(document.querySelectorAll('.field-checkbox:checked'))
                .map(checkbox => checkbox.value);
            
            checkMatrixDateSelection();
        }

        function uncheckField(fieldKey) {
            const checkbox = document.getElementById(`field-${fieldKey}`);
            if (checkbox) {
                checkbox.checked = false;
            }
        }

        function getSelectedMatrixDateFields() {
            return MATRIX_DATE_FIELDS.filter(field => selectedFields.includes(field));
        }

        function isMatrixViewSelected() {
            return document.querySelector('input[name="view_type"][value="matrix"]')?.checked === true;
        }

        function enforceMatrixDateExclusion(checkbox) {
            if (!isMatrixViewSelected() || !checkbox.checked) {
                return;
            }
            if (checkbox.value === COMPETENCIA_FIELD) {
                uncheckField(MOVIMENTO_FIELD);
            } else if (checkbox.value === MOVIMENTO_FIELD) {
                uncheckField(COMPETENCIA_FIELD);
            }
        }

        function enforceMatrixDateExclusionOnViewSwitch() {
            const selectedDates = MATRIX_DATE_FIELDS.filter(field =>
                document.getElementById(`field-${field}`)?.checked
            );
            if (selectedDates.length > 1) {
                uncheckField(MOVIMENTO_FIELD);
            }
        }

        // Exibe controles de matriz quando competência OU movimento está selecionado
        function checkMatrixDateSelection() {
            const hasMatrixDate = getSelectedMatrixDateFields().length > 0;
            const visualizationControls = document.getElementById('visualization-controls');
            
            if (hasMatrixDate) {
                visualizationControls.style.display = 'block';
            } else {
                visualizationControls.style.display = 'none';
                document.querySelector('input[name="view_type"][value="list"]').checked = true;
            }
        }

        // Open filter modal
        function openFilterModal() {
            document.getElementById('filter-modal').classList.remove('hidden');
        }

        // Close filter modal
        function closeFilterModal() {
            document.getElementById('filter-modal').classList.add('hidden');
            resetFilterModal();
        }

        // Reset filter modal
        function resetFilterModal() {
            document.getElementById('filter-field').value = '';
            document.getElementById('filter-operator').value = '';
            document.getElementById('filter-operator').innerHTML = '<option value="">Selecione um operador...</option>';
            resetFilterValueInput();
        }

        function resetFilterValueInput() {
            document.getElementById('filter-value-container').innerHTML = `
                <label class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                <input type="text" id="filter-value" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Digite o valor...">
            `;
        }

        function updateFilterValueInput() {
            const fieldKey = document.getElementById('filter-field').value;
            const field = fieldKey ? availableFields[fieldKey] : null;
            const container = document.getElementById('filter-value-container');

            if (fieldKey === 'PRD_IDADE') {
                container.innerHTML = `
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                    <input type="number" min="0" id="filter-value" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Informe a idade...">
                `;
            } else {
                resetFilterValueInput();
            }
        }

        // Populate filter field options
        function populateFilterFieldOptions() {
            const select = document.getElementById('filter-field');
            select.innerHTML = '<option value="">Selecione um campo...</option>';
            
            Object.keys(availableFields).forEach(fieldKey => {
                const field = availableFields[fieldKey];
                const option = document.createElement('option');
                option.value = fieldKey;
                option.textContent = field.label;
                select.appendChild(option);
            });
        }

        // Update filter operators based on selected field
        function updateFilterOperators() {
            const fieldKey = document.getElementById('filter-field').value;
            const operatorSelect = document.getElementById('filter-operator');
            
            operatorSelect.innerHTML = '<option value="">Selecione um operador...</option>';
            
            if (fieldKey && availableFields[fieldKey]) {
                const operators = availableFields[fieldKey].operators || [];
                const operatorLabels = {
                    '=': 'Igual a',
                    '>': 'Maior que',
                    '<': 'Menor que',
                    '>=': 'Maior ou igual',
                    '<=': 'Menor ou igual',
                    'like': 'Contém',
                    'starts_with': 'Inicia com',
                    'ends_with': 'Termina com',
                    'between': 'Entre',
                    'in': 'Em lista'
                };
                
                operators.forEach(op => {
                    const option = document.createElement('option');
                    option.value = op;
                    option.textContent = operatorLabels[op] || op;
                    operatorSelect.appendChild(option);
                });
            }
        }

        // Save filter
        function saveFilter() {
            const field = document.getElementById('filter-field').value;
            const operator = document.getElementById('filter-operator').value;
            const value = document.getElementById('filter-value').value;
            
            if (!field || !operator || !value) {
                alert('Por favor, preencha todos os campos do filtro.');
                return;
            }
            
            const filter = {
                id: ++filterCounter,
                field: field,
                operator: operator,
                value: value,
                label: `${availableFields[field].label} ${operator} ${value}`
            };
            
            appliedFilters.push(filter);
            renderFilters();
            closeFilterModal();
        }

        // Render applied filters
        function renderFilters() {
            const container = document.getElementById('filters-container');
            const noFiltersMessage = document.getElementById('no-filters-message');
            
            if (appliedFilters.length === 0) {
                noFiltersMessage.style.display = 'block';
                return;
            }
            
            noFiltersMessage.style.display = 'none';
            
            const filtersHtml = appliedFilters.map(filter => `
                <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <span class="text-sm text-blue-800">${filter.label}</span>
                    <button onclick="removeFilter(${filter.id})" class="text-red-600 hover:text-red-800 text-sm">
                        ✕ Remover
                    </button>
                </div>
            `).join('');
            
            container.innerHTML = noFiltersMessage.outerHTML + filtersHtml;
        }

        // Remove filter
        function removeFilter(filterId) {
            appliedFilters = appliedFilters.filter(f => f.id !== filterId);
            renderFilters();
        }

        // Funções já definidas no módulo compartilhado - usando aliases acima

        // Generate report
        async function generateReport(format) {
            if (selectedFields.length === 0) {
                alert('Por favor, selecione pelo menos um campo para o relatório.');
                return;
            }
            
            // Detectar tipo de visualização
            const viewType = document.querySelector('input[name="view_type"]:checked')?.value || 'list';
            const isMatrixView = viewType === 'matrix';
            
            const selectedMatrixDates = getSelectedMatrixDateFields();
            if (isMatrixView && selectedMatrixDates.length === 0) {
                alert('Para visualização em matriz, selecione "Data Competência" ou "Data Movimento".');
                return;
            }
            if (isMatrixView && selectedMatrixDates.length > 1) {
                alert('Não é possível selecionar "Data Competência" e "Data Movimento" ao mesmo tempo na matriz.');
                return;
            }

            // Mostrar loading específico para matriz
            if (isMatrixView) {
                showMatrixLoading();
            } else {
                showLoading();
            }
            
            // Verificar se há filtro de competência
            const hasCompetenciaFilter = appliedFilters.some(filter => {
                const fieldKey = filter.field.toLowerCase();
                const fieldLabel = availableFields[filter.field]?.label?.toLowerCase() || '';
                
                return fieldKey.includes('competencia') || 
                       fieldKey.includes('periodo') ||
                       fieldKey.includes('data') ||
                       fieldKey.includes('ano') ||
                       fieldKey.includes('mes') ||
                       fieldKey.includes('cmp') ||  // Para prd_cmp
                       fieldKey.includes('mvm') ||  // Para PAP_MVM (APAC)
                       fieldLabel.includes('competencia') ||
                       fieldLabel.includes('período') ||
                       fieldLabel.includes('data');
            });
            
            if (!hasCompetenciaFilter && !isMatrixView) {
                const confirmMessage = `⚠️ ATENÇÃO: Você não aplicou nenhum filtro de competência/período.\n\n` +
                    `Isso pode resultar em um relatório muito extenso e demorado para processar.\n\n` +
                    `Deseja continuar mesmo assim?`;
                
                if (!confirm(confirmMessage)) {
                    hideLoading(); // Reabilitar botões antes de sair
                    return; // Usuário cancelou
                }
            }
            
            // Variável para controlar cancelamento
            window.currentRequest = null;
            
            const payload = {
                fields: selectedFields,
                filters: (() => {
                    let allFilters = appliedFilters.map(f => ({
                        field: f.field,
                        operator: f.operator,
                        value: f.value
                    }));

                    if (document.getElementById('sus-paulista-filter')?.checked) {
                        allFilters.push({
                            field: 'filter_sus_paulista',
                            operator: '=',
                            value: true
                        });
                    }

                    return allFilters;
                })(),
                format: format
            };
            
            try {
                const controller = new AbortController();
                window.currentRequest = controller;
                
                // Escolher rota baseada no tipo de visualização
                const routeUrl = isMatrixView ? 
                    '{{ route("relatorios.generate-matrix") }}' : 
                    '{{ route("relatorios.generate") }}';
                
                const response = await fetch(routeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload),
                    signal: controller.signal
                });
                
                if (format === 'html') {
                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('Server error:', errorText);
                        throw new Error(`Erro do servidor: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    // Show SQL if available
                    if (data.sql) {
                        showSQL(data.sql, data.bindings);
                    }
                    
                    renderResults(data);
                    
                    // CORREÇÃO: Garantir que botões sejam reabilitados após renderizar matriz
                    if (data.type === 'matrix') {
                        hideLoading();
                    }
                } else {
                    if (!response.ok) {
                        // Try to get error message from response
                        let errorMessage = `Erro ao exportar: ${response.status}`;
                        try {
                            const errorData = await response.json();
                            if (errorData.error) {
                                errorMessage = errorData.error;
                            }
                        } catch (e) {
                            // Response is not JSON, use default message
                        }
                        throw new Error(errorMessage);
                    }
                    
                    // Handle file downloads
                    await handleFileDownload(response, format, `relatorio.${format === 'excel' ? 'xlsx' : format}`);
                }
            } catch (error) {
                console.error('Error generating report:', error);
                
                // Verificar se foi cancelamento
                if (error.name === 'AbortError') {
                    showCancellation();
                } else {
                    showError(error.message);
                }
            } finally {
                hideLoading();
                window.currentRequest = null;
            }
        }

        // Funções renderResults, renderListResults e renderMatrixResults já definidas no módulo compartilhado
        // Usando aliases definidos acima
        
        // Função para toggle de visualização de matriz (específica desta view)
        window.toggleMatrixView = function(viewType) {
            const table = document.querySelector('.matrix-table');
            if (!table) return;
            
            if (viewType === 'compact') {
                table.classList.add('text-xs');
                table.querySelectorAll('th, td').forEach(cell => {
                    cell.classList.add('px-1', 'py-1');
                    cell.classList.remove('px-2', 'py-2', 'px-3');
                });
            } else {
                table.classList.remove('text-xs');
                table.querySelectorAll('th, td').forEach(cell => {
                    cell.classList.remove('px-1', 'py-1');
                    if (cell.classList.contains('sticky-left')) {
                        cell.classList.add('px-3', 'py-2');
                    } else {
                        cell.classList.add('px-2', 'py-2');
                    }
                });
            }
        };

        // Toggle visualization help
        function toggleVisualizationHelp() {
            const helpDiv = document.getElementById('visualization-help');
            if (helpDiv.style.display === 'none') {
                helpDiv.style.display = 'block';
            } else {
                helpDiv.style.display = 'none';
            }
        }

        // Função cancelSearch já definida no módulo compartilhado - usando alias acima

        // Add responsive handling for window resize
        window.addEventListener('resize', function() {
            const matrixContainer = document.querySelector('.matrix-container');
            if (matrixContainer) {
                const isMobile = window.innerWidth < 768;
                if (isMobile) {
                    matrixContainer.classList.add('mobile-matrix');
                } else {
                    matrixContainer.classList.remove('mobile-matrix');
                }
            }
        });
    </script>
@endsection