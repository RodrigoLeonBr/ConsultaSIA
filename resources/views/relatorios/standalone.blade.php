@extends('layouts.modern')

@section('title', 'Relatórios Standalone - ConsultaProd')

@section('content')
<style>
    /* Garantir que o botão Adicionar Filtro seja sempre visível */
    #add-filter {
        min-width: 140px !important;
        white-space: nowrap !important;
        height: 32px !important; 
        line-height: 20px !important;
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* Melhorar layout responsivo do painel de filtros */
    @media (max-width: 1024px) {
        .lg\\:col-span-2 {
            margin-top: 1rem;
        }
    }
    
    /* Garantir que o container de filtros não interfira */
    #filters-container {
        min-height: 128px;
        position: relative;
    }
    
    /* Estilo para o botão sempre visível */
    .filter-button-container {
        position: sticky;
        top: 0;
        z-index: 10;
        background: white;
        padding: 0.5rem 0;
    }
</style>

<div class="min-h-screen bg-gray-100">
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
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
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
                <div class="lg:col-span-1">
                    <!-- Botão sempre visível em container separado -->
                    <div class="filter-button-container">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Filtros Avançados</h3>
                            <button id="add-filter" 
                                    style="background-color: #3b82f6 !important; color: white !important; padding: 0.25rem 0.75rem !important; border-radius: 0.25rem !important; font-size: 0.875rem !important; display: inline-block !important; visibility: visible !important; opacity: 1 !important; min-width: 140px !important; white-space: nowrap !important; height: 32px !important; line-height: 20px !important; border: none !important; cursor: pointer !important;"
                                    onmouseover="this.style.backgroundColor='#2563eb'"
                                    onmouseout="this.style.backgroundColor='#3b82f6'">
                                + Adicionar Filtro
                            </button>
                        </div>
                    </div>
                    
                    <!-- Container de filtros separado -->
                    <div id="filters-container" class="space-y-3 min-h-32 border rounded-lg p-4">
                        <p class="text-gray-500 text-sm" id="no-filters-message">Nenhum filtro adicionado. Clique em "Adicionar Filtro" para começar.</p>
                        <div id="filters-list" class="space-y-3 mt-3">
                            <!-- Filtros serão inseridos aqui -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-6 flex flex-wrap gap-3">
                <button id="generate-report" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition-colors duration-200">
                    🔍 Gerar Relatório
                </button>
                <button id="cancel-search" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition-colors duration-200 hidden">
                    ❌ Cancelar Pesquisa
                </button>
                <button id="export-excel" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors duration-200">
                    📊 Exportar Excel
                </button>
                <button id="export-pdf" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition-colors duration-200">
                    📄 Exportar PDF
                </button>
                <button id="export-csv" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition-colors duration-200">
                    📋 Exportar CSV
                </button>
                <a href="{{ route('relatorios.test-excel') }}" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 inline-block transition-colors duration-200">
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

<!-- Filter Modal Template -->
<div id="filter-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Configurar Filtro</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Campo</label>
                        <select id="filter-field" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione um campo...</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Operador</label>
                        <select id="filter-operator" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione um operador...</option>
                        </select>
                    </div>
                    
                    <div id="filter-value-container">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                        <input type="text" id="filter-value" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Digite o valor...">
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button id="cancel-filter" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                        Cancelar
                    </button>
                    <button id="save-filter" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors duration-200">
                        Adicionar Filtro
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Report Builder JavaScript
    let availableFields = {};
    let selectedFields = [];
    let appliedFilters = [];
    let filterCounter = 0;

    // Initialize the report builder
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Inicializando Report Builder Standalone');
        loadAvailableFields();
        setupEventListeners();
        
        // Garantir que o botão seja sempre visível
        ensureAddFilterButtonVisible();
    });

    // Função para garantir que o botão seja sempre visível
    function ensureAddFilterButtonVisible() {
        const addFilterBtn = document.getElementById('add-filter');
        if (addFilterBtn) {
            addFilterBtn.style.display = 'inline-block';
            addFilterBtn.style.visibility = 'visible';
            addFilterBtn.style.opacity = '1';
            addFilterBtn.style.minWidth = '140px';
            addFilterBtn.style.whiteSpace = 'nowrap';
            addFilterBtn.style.height = '32px';
            addFilterBtn.style.lineHeight = '20px';
            
            console.log('✅ Botão Adicionar Filtro garantido como visível');
        }
    }

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
                       class="field-checkbox mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
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
                updateSelectedFields();
            }
        });

        // Filter management
        document.getElementById('add-filter').addEventListener('click', function() {
            console.log('🔵 Botão Adicionar Filtro clicado');
            openFilterModal();
        });
        document.getElementById('cancel-filter').addEventListener('click', closeFilterModal);
        document.getElementById('save-filter').addEventListener('click', saveFilter);
        
        // Field change in filter modal
        document.getElementById('filter-field').addEventListener('change', updateFilterOperators);
        
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
        console.log('📋 Campos selecionados:', selectedFields);
    }

    // Open filter modal
    function openFilterModal() {
        console.log('🔓 Abrindo modal de filtro');
        document.getElementById('filter-modal').classList.remove('hidden');
    }

    // Close filter modal
    function closeFilterModal() {
        console.log('🔒 Fechando modal de filtro');
        document.getElementById('filter-modal').classList.add('hidden');
        resetFilterModal();
    }

    // Reset filter modal
    function resetFilterModal() {
        document.getElementById('filter-field').value = '';
        document.getElementById('filter-operator').value = '';
        document.getElementById('filter-value').value = '';
        document.getElementById('filter-operator').innerHTML = '<option value="">Selecione um operador...</option>';
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
        console.log('✅ Filtro adicionado:', filter);
        renderFilters();
        closeFilterModal();
        
        // Garantir que o botão continue visível após adicionar filtro
        ensureAddFilterButtonVisible();
    }

    // Render applied filters - VERSÃO CORRIGIDA
    function renderFilters() {
        console.log('🎨 Renderizando filtros:', appliedFilters);
        
        const noFiltersMessage = document.getElementById('no-filters-message');
        const filtersList = document.getElementById('filters-list');
        
        if (appliedFilters.length === 0) {
            noFiltersMessage.style.display = 'block';
            filtersList.innerHTML = '';
            console.log('📝 Mostrando mensagem: nenhum filtro');
            return;
        }
        
        noFiltersMessage.style.display = 'none';
        
        const filtersHtml = appliedFilters.map(filter => `
            <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded-lg p-3">
                <span class="text-sm text-blue-800">${filter.label}</span>
                <button onclick="removeFilter(${filter.id})" class="text-red-600 hover:text-red-800 text-sm transition-colors duration-200">
                    ✕ Remover
                </button>
            </div>
        `).join('');
        
        filtersList.innerHTML = filtersHtml;
        console.log('✅ Filtros renderizados com sucesso');
        
        // Garantir que o botão continue visível após renderizar
        ensureAddFilterButtonVisible();
    }

    // Remove filter
    function removeFilter(filterId) {
        console.log('🗑️ Removendo filtro:', filterId);
        appliedFilters = appliedFilters.filter(f => f.id !== filterId);
        renderFilters();
        
        // Garantir que o botão continue visível após remover filtro
        ensureAddFilterButtonVisible();
    }

    // Load lookup data for a field
    async function loadLookupData(field, search = '') {
        try {
            const url = new URL('{{ route("relatorios.lookup") }}');
            url.searchParams.append('field', field);
            if (search) url.searchParams.append('search', search);
            
            const response = await fetch(url);
            return await response.json();
        } catch (error) {
            console.error('Error loading lookup data:', error);
            return [];
        }
    }

    // Show loading indicator
    function showLoading() {
        document.getElementById('loading-indicator').classList.remove('hidden');
        document.getElementById('results-container').innerHTML = '';
        document.getElementById('cancel-search').classList.remove('hidden');
        
        // Disable buttons
        const buttons = ['generate-report', 'export-excel', 'export-pdf', 'export-csv'];
        buttons.forEach(id => {
            const btn = document.getElementById(id);
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        });
    }

    // Hide loading indicator
    function hideLoading() {
        document.getElementById('loading-indicator').classList.add('hidden');
        document.getElementById('cancel-search').classList.add('hidden');
        
        // Enable buttons
        const buttons = ['generate-report', 'export-excel', 'export-pdf', 'export-csv'];
        buttons.forEach(id => {
            const btn = document.getElementById(id);
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        });
    }

    // Show SQL
    function showSQL(sql, bindings) {
        const sqlPanel = document.getElementById('sql-panel');
        const sqlDisplay = document.getElementById('sql-display');
        
        let formattedSQL = sql;
        if (bindings && bindings.length > 0) {
            formattedSQL += '\n\nBindings: ' + JSON.stringify(bindings, null, 2);
        }
        
        sqlDisplay.textContent = formattedSQL;
        sqlPanel.style.display = 'block';
    }

    function showError(message, sql = null, bindings = null) {
        document.getElementById('results-container').innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Erro ao gerar relatório</h3>
                        <div class="mt-2 text-sm text-red-700"><p>${message}</p></div>
                    </div>
                </div>
            </div>
        `;
        if (sql) {
            showSQL(sql, bindings);
        }
    }

    // Generate report
    async function generateReport(format) {
        if (selectedFields.length === 0) {
            alert('Por favor, selecione pelo menos um campo para o relatório.');
            return;
        }
        
        // Verificar se há filtro de competência
        const hasCompetenciaFilter = appliedFilters.some(filter => 
            filter.field.toLowerCase().includes('competencia') || 
            filter.field.toLowerCase().includes('periodo') ||
            filter.field.toLowerCase().includes('data') ||
            filter.field.toLowerCase().includes('ano') ||
            filter.field.toLowerCase().includes('mes')
        );
        
        if (!hasCompetenciaFilter) {
            const confirmMessage = `⚠️ ATENÇÃO: Você não aplicou nenhum filtro de competência/período.\n\n` +
                `Isso pode resultar em um relatório muito extenso e demorado para processar.\n\n` +
                `Deseja continuar mesmo assim?`;
            
            if (!confirm(confirmMessage)) {
                return; // Usuário cancelou
            }
        }
        
        showLoading();
        
        // Variável para controlar cancelamento
        window.currentRequest = null;
        
        const payload = {
            fields: selectedFields,
            filters: appliedFilters.map(f => ({
                field: f.field,
                operator: f.operator,
                value: f.value
            })),
            format: format
        };
        
        try {
            const controller = new AbortController();
            window.currentRequest = controller;
            
            const response = await fetch('{{ route("relatorios.generate") }}', {
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
                    let message = `Erro do servidor: ${response.status}`;
                    let sql = null;
                    let bindings = null;
                    const errorText = await response.text();
                    console.error('Server error:', errorText);
                    try {
                        const errorData = JSON.parse(errorText);
                        if (errorData.error) message = errorData.error;
                        sql = errorData.sql ?? null;
                        bindings = errorData.bindings ?? null;
                    } catch (e) {}
                    showError(message);
                    if (sql) showSQL(sql, bindings);
                    return;
                }
                
                const data = await response.json();
                
                if (data.error) {
                    showError(data.error);
                    if (data.sql) showSQL(data.sql, data.bindings);
                    return;
                }
                
                // Show SQL if available
                if (data.sql) {
                    showSQL(data.sql, data.bindings);
                }
                
                renderResults(data);
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
                const blob = await response.blob();
                
                // Check if blob is actually an error response
                if (blob.type === 'application/json') {
                    const text = await blob.text();
                    const errorData = JSON.parse(text);
                    throw new Error(errorData.error || 'Erro desconhecido na exportação');
                }
                
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `relatorio.${format === 'excel' ? 'xlsx' : format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            }
        } catch (error) {
            console.error('Error generating report:', error);
            
            // Verificar se foi cancelamento
            if (error.name === 'AbortError') {
                document.getElementById('results-container').innerHTML = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    Pesquisa Cancelada
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>A pesquisa foi cancelada pelo usuário.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Show error in results container
                document.getElementById('results-container').innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Erro ao gerar relatório</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>${error.message}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        } finally {
            hideLoading();
            window.currentRequest = null;
            
            // Garantir que o botão continue visível após qualquer operação
            ensureAddFilterButtonVisible();
        }
    }

    // Render results table
    function renderResults(data) {
        const container = document.getElementById('results-container');
        
        if (!data.data || data.data.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum resultado encontrado com os filtros aplicados.</p>';
            return;
        }
        
        // Get headers from first row
        const firstRow = data.data[0];
        const headers = Object.keys(firstRow).map(header => `<th class="px-2 py-1 border text-left text-xs font-medium">${header}</th>`).join('');
        
        const rows = data.data.map(row => {
            const cells = Object.values(row).map(value => `<td class="px-2 py-1 border text-xs">${value || ''}</td>`).join('');
            return `<tr>${cells}</tr>`;
        }).join('');
        
        let totalsHtml = '';
        if (data.totals && Object.keys(data.totals).length > 0) {
            const totalRows = Object.entries(data.totals).map(([label, value]) => 
                `<tr class="bg-blue-50"><td class="px-2 py-1 border font-semibold text-xs">${label}</td><td class="px-2 py-1 border font-semibold text-xs">${value}</td></tr>`
            ).join('');
            
            totalsHtml = `
                <div class="mt-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-2">Totais</h4>
                    <table class="border-collapse border border-gray-300">
                        ${totalRows}
                    </table>
                </div>
            `;
        }
        
        container.innerHTML = `
            <div class="mb-4">
                <p class="text-sm text-gray-600">Total de registros: <strong>${data.total}</strong></p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse border border-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            ${headers}
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
            </div>
            ${totalsHtml}
        `;
    }

    // Cancel search function
    function cancelSearch() {
        if (window.currentRequest) {
            window.currentRequest.abort();
            window.currentRequest = null;
        }
        hideLoading();
        
        // Garantir que o botão continue visível após cancelar
        ensureAddFilterButtonVisible();
    }

    // Monitorar mudanças no DOM para garantir que o botão seja sempre visível
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' || mutation.type === 'attributes') {
                ensureAddFilterButtonVisible();
            }
        });
    });

    // Observar mudanças no container de filtros
    const filtersContainer = document.getElementById('filters-container');
    if (filtersContainer) {
        observer.observe(filtersContainer, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    }

    console.log('🎯 Report Builder Standalone carregado com sucesso!');
</script>
@endsection
