@extends('layouts.modern')

@section('title', 'Relatório de APAC')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Relatório de APAC</h1>
        <p class="text-gray-600 mt-1">Produção APAC por prestador/procedimento (tabelas s_pap + s_apa)</p>
    </div>
</div>
@endsection

@section('content')
    <style>
        /* Garantir que o botão Adicionar Filtro seja sempre visível */
        #add-filter {
            min-width: 140px;
            white-space: nowrap;
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
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Relatório de APAC</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Gera relatórios da <strong>produção APAC</strong> com procedimentos internos (<code>s_pap</code>) e dados do paciente/guia (<code>s_apa</code>), no mesmo padrão da
                                <a href="{{ route('relatorios.bpi.index') }}" class="font-medium text-blue-800 hover:text-blue-900 underline">Produção Individualizada</a>.
                            </p>
                            <p class="mt-1"><strong>OCI:</strong> use o filtro "Filtrar apenas OCI" para procedimentos principais que iniciam com "09".</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Builder Interface -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <!-- Field Selection Panel -->
                        <div class="lg:col-span-1">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Seleção de Campos</h3>
                            <div class="space-y-2 max-h-96 overflow-y-auto border rounded-lg p-4">
                                <div id="field-checkboxes">
                                    <!-- Loading indicator -->
                                    <div id="fields-loading" class="flex items-center justify-center py-4">
                                        <svg class="animate-spin h-5 w-5 text-blue-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="text-sm text-gray-600">Carregando campos...</span>
                                    </div>
                                    <!-- Error message container -->
                                    <div id="fields-error" class="hidden"></div>
                                    <!-- Retry button -->
                                    <div id="fields-retry" class="hidden mt-2">
                                        <button onclick="loadAvailableFields()" class="text-sm text-blue-600 hover:text-blue-800 underline">
                                            Tentar novamente
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters Panel -->
                        <div class="lg:col-span-2">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Filtros Avançados</h3>
                                <button id="add-filter" 
                                        style="background-color: #3b82f6 !important; color: white !important; padding: 0.25rem 0.75rem !important; border-radius: 0.25rem !important; font-size: 0.875rem !important; display: inline-block !important; visibility: visible !important; opacity: 1 !important; min-width: 140px !important; white-space: nowrap !important; height: 32px !important; line-height: 20px !important; border: none !important; cursor: pointer !important;"
                                        onmouseover="this.style.backgroundColor='#2563eb'"
                                        onmouseout="this.style.backgroundColor='#3b82f6'">
                                    + Adicionar Filtro
                                </button>
                            </div>
                            
                            <!-- Special OCI Filter -->
                            <div class="mb-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                                <label class="flex items-center">
                                    <input type="checkbox" id="oci-filter" class="mr-2 rounded border-gray-300">
                                    <span class="text-sm font-medium text-orange-800">
                                        🔍 Filtrar apenas OCI (Órteses, Próteses e Materiais - procedimentos 09*)
                                    </span>
                                </label>
                                <p class="text-xs text-orange-600 mt-1">1.531 registros OCI disponíveis</p>
                            </div>

                            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                                <label class="flex items-center">
                                    <input type="checkbox" id="sus-paulista-filter" class="mr-2 rounded border-gray-300">
                                    <span class="text-sm font-medium text-emerald-800">
                                        Somente procedimentos com Tabela SUS Paulista (SIA)
                                    </span>
                                </label>
                                <p class="text-xs text-emerald-600 mt-1">Oculta linhas sem cadastro vigente na tabela SUS Paulista para a competência.</p>
                            </div>
                            
                            <div class="min-h-32 border rounded-lg p-4">
                                <p class="text-gray-500 text-sm" id="no-filters-message">Nenhum filtro adicionado. Clique em "Adicionar Filtro" para começar.</p>
                                <div id="filters-list" class="space-y-3 mt-3">
                                    <!-- Filtros serão inseridos aqui -->
                                </div>
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
                                <span class="ml-2 text-sm text-gray-700 group-hover:text-gray-900">Matriz por Competência</span>
                                <span class="ml-1 text-xs text-gray-400 hidden sm:inline">(pivot table)</span>
                            </label>
                        </div>
                        <div id="visualization-help" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg" style="display: none;">
                            <div class="text-xs text-blue-800">
                                <p class="mb-2"><strong>Lista Simples:</strong> Exibe dados em formato de tabela tradicional, uma linha por registro.</p>
                                <p><strong>Matriz por Competência:</strong> Transforma competências em colunas e outros campos em linhas. Ideal para:</p>
                                <ul class="list-disc list-inside mt-1 ml-2">
                                    <li>Análises temporais e comparativas</li>
                                    <li>Visualizar tendências por período</li>
                                    <li>Comparar valores entre diferentes meses</li>
                                </ul>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <strong>Dica:</strong> A matriz funciona melhor com filtros de competência para limitar o período.
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 flex flex-wrap gap-3">
                        <button id="generate-report" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            🔍 Gerar Relatório APAC
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
                    </div>
                </div>
            </div>

            <!-- SQL Debug Panel -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6" id="sql-panel" style="display: none;">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">SQL Gerado</h3>
                    <div class="bg-gray-100 p-4 rounded-lg">
                        <pre id="sql-display" class="text-sm text-gray-800 whitespace-pre-wrap"></pre>
                    </div>
                </div>
            </div>

            <!-- Results Panel -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Resultados</h3>
                    
                    <!-- Loading Indicator -->
                    <div id="loading-indicator" class="hidden text-center py-8">
                        <div class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-gray-600">Processando relatório APAC...</span>
                        </div>
                    </div>
                    
                    <div id="results-container">
                        <p class="text-gray-500 text-center py-8">Configure os campos e filtros acima e clique em "Gerar Relatório APAC" para visualizar os dados.</p>
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
        // Report Builder JavaScript for APAC
        let availableFields = {};
        let selectedFields = [];
        let appliedFilters = [];
        let filterCounter = 0;
        const COMPETENCIA_FIELD = 'PAP_CMP';
        const MOVIMENTO_FIELD = 'PAP_MVM';
        const MATRIX_DATE_FIELDS = [COMPETENCIA_FIELD, MOVIMENTO_FIELD];
        
        // Aliases para funções do módulo compartilhado
        const showLoading = RelatoriosBase.showLoading;
        const hideLoading = RelatoriosBase.hideLoading;
        const showMatrixLoading = RelatoriosBase.showMatrixLoading;
        const showSQL = RelatoriosBase.showSQL;
        const loadLookupData = (field, search) => RelatoriosBase.loadLookupData(field, search, '{{ route("relatorios.apac.lookup") }}');
        const renderResults = RelatoriosBase.renderResults;
        const renderListResults = RelatoriosBase.renderListResults;
        const renderMatrixResults = RelatoriosBase.renderMatrixResults;
        const cancelSearch = RelatoriosBase.cancelSearch;
        const showError = RelatoriosBase.showError;
        const handleReportHttpError = RelatoriosBase.handleReportHttpError;
        const showCancellation = RelatoriosBase.showCancellation;
        const handleFileDownload = RelatoriosBase.handleFileDownload;

        // Load available fields from server
        async function loadAvailableFields() {
            const fieldCheckboxes = document.getElementById('field-checkboxes');
            const fieldsLoading = document.getElementById('fields-loading');
            const fieldsError = document.getElementById('fields-error');
            const fieldsRetry = document.getElementById('fields-retry');
            
            if (!fieldCheckboxes) {
                throw new Error('Elemento field-checkboxes não encontrado');
            }
            
            try {
                // Mostrar loading
                if (fieldsLoading) fieldsLoading.classList.remove('hidden');
                if (fieldsError) fieldsError.classList.add('hidden');
                if (fieldsRetry) fieldsRetry.classList.add('hidden');
                
                const url = '{{ route("relatorios.apac.fields") }}';
                console.log('Loading APAC fields from:', url);
                
                const response = await fetch(url);
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('APAC Fields loaded:', data);
                
                if (!data || !data.fields) {
                    throw new Error('Resposta inválida do servidor: campos não encontrados');
                }
                
                availableFields = data.fields;
                console.log('Available fields:', availableFields);
                
                if (Object.keys(availableFields).length === 0) {
                    throw new Error('Nenhum campo disponível');
                }
                
                // Esconder loading
                if (fieldsLoading) fieldsLoading.classList.add('hidden');
                
                // Renderizar campos
                renderFieldCheckboxes();
                populateFilterFieldOptions();
                
                return true;
            } catch (error) {
                console.error('Error loading APAC fields:', error);
                
                // Esconder loading
                if (fieldsLoading) fieldsLoading.classList.add('hidden');
                
                // Mostrar erro
                if (fieldsError) {
                    fieldsError.classList.remove('hidden');
                    fieldsError.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-red-800">Erro ao carregar campos</p>
                                    <p class="text-xs text-red-600 mt-1">${error.message}</p>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                // Mostrar botão de retry
                if (fieldsRetry) fieldsRetry.classList.remove('hidden');
                
                throw error;
            }
        }
        
        // Tornar loadAvailableFields disponível globalmente para o botão de retry
        window.loadAvailableFields = loadAvailableFields;

        // Initialize the report builder
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar se elementos existem antes de inicializar
            const fieldCheckboxes = document.getElementById('field-checkboxes');
            if (!fieldCheckboxes) {
                console.error('Elemento field-checkboxes não encontrado');
                return;
            }
            
            // Carregar campos primeiro, depois configurar event listeners
            loadAvailableFields().then(() => {
                setupEventListeners();
            }).catch((error) => {
                console.error('Erro ao inicializar:', error);
                // Mesmo com erro, tentar configurar event listeners básicos
                setupEventListeners();
            });
        });

        // Setup event listeners
        function setupEventListeners() {
            // Field selection - usar event delegation para campos carregados dinamicamente
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('field-checkbox')) {
                    enforceMatrixDateExclusion(e.target);
                    updateSelectedFields();
                }
                if (e.target.name === 'view_type' && e.target.value === 'matrix') {
                    enforceMatrixDateExclusionOnViewSwitch();
                }
            });

            // Filter management - verificar se elementos existem antes de adicionar listeners
            const addFilterBtn = document.getElementById('add-filter');
            if (addFilterBtn) {
                addFilterBtn.addEventListener('click', openFilterModal);
            }
            
            const cancelFilterBtn = document.getElementById('cancel-filter');
            if (cancelFilterBtn) {
                cancelFilterBtn.addEventListener('click', closeFilterModal);
            }
            
            const saveFilterBtn = document.getElementById('save-filter');
            if (saveFilterBtn) {
                saveFilterBtn.addEventListener('click', saveFilter);
            }
            
            // Field change in filter modal
            const filterField = document.getElementById('filter-field');
            if (filterField) {
                filterField.addEventListener('change', updateFilterOperators);
            }
            
            // Report generation
            const generateReportBtn = document.getElementById('generate-report');
            if (generateReportBtn) {
                generateReportBtn.addEventListener('click', () => generateReport('html'));
            }
            
            const cancelSearchBtn = document.getElementById('cancel-search');
            if (cancelSearchBtn) {
                cancelSearchBtn.addEventListener('click', cancelSearch);
            }
            
            const exportExcelBtn = document.getElementById('export-excel');
            if (exportExcelBtn) {
                exportExcelBtn.addEventListener('click', () => generateReport('excel'));
            }
            
            const exportPdfBtn = document.getElementById('export-pdf');
            if (exportPdfBtn) {
                exportPdfBtn.addEventListener('click', () => generateReport('pdf'));
            }
            
            const exportCsvBtn = document.getElementById('export-csv');
            if (exportCsvBtn) {
                exportCsvBtn.addEventListener('click', () => generateReport('csv'));
            }
        }

        // Render field checkboxes
        function renderFieldCheckboxes() {
            const container = document.getElementById('field-checkboxes');
            if (!container) {
                console.error('Container field-checkboxes não encontrado para renderizar campos');
                return;
            }
            
            // Esconder loading, error e retry
            const fieldsLoading = document.getElementById('fields-loading');
            const fieldsError = document.getElementById('fields-error');
            const fieldsRetry = document.getElementById('fields-retry');
            
            if (fieldsLoading) fieldsLoading.classList.add('hidden');
            if (fieldsError) {
                fieldsError.classList.add('hidden');
                fieldsError.innerHTML = '';
            }
            if (fieldsRetry) fieldsRetry.classList.add('hidden');
            
            // Remover apenas os checkboxes existentes (não os containers de loading/error/retry)
            // Percorrer todos os filhos e remover apenas os que contêm field-checkbox
            Array.from(container.children).forEach(child => {
                // Manter apenas os containers de loading, error e retry
                if (child.id === 'fields-loading' || 
                    child.id === 'fields-error' || 
                    child.id === 'fields-retry') {
                    return; // Não remover
                }
                
                // Se contém um field-checkbox, remover
                if (child.querySelector && child.querySelector('.field-checkbox')) {
                    child.remove();
                }
                
                // Se é uma mensagem de "nenhum campo", remover
                if (child.tagName === 'P' && child.classList.contains('text-gray-500')) {
                    child.remove();
                }
            });
            
            // Se não houver campos disponíveis, mostrar mensagem
            if (!availableFields || Object.keys(availableFields).length === 0) {
                const emptyMsg = document.createElement('p');
                emptyMsg.className = 'text-gray-500 text-sm';
                emptyMsg.textContent = 'Nenhum campo disponível';
                container.appendChild(emptyMsg);
                return;
            }
            
            // Criar um fragmento para melhor performance
            const fragment = document.createDocumentFragment();
            
            Object.keys(availableFields).forEach(fieldKey => {
                const field = availableFields[fieldKey];
                if (!field || !field.label) {
                    console.warn('Campo inválido:', fieldKey);
                    return;
                }
                
                const div = document.createElement('div');
                div.className = 'flex items-center py-1';
                div.innerHTML = `
                    <input type="checkbox" id="field-${fieldKey}" value="${fieldKey}" 
                           class="field-checkbox mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="field-${fieldKey}" class="text-sm text-gray-700 cursor-pointer hover:text-gray-900">
                        ${field.label}
                    </label>
                `;
                fragment.appendChild(div);
            });
            
            container.appendChild(fragment);
        }

        function updateSelectedFields() {
            selectedFields = Array.from(document.querySelectorAll('.field-checkbox:checked'))
                .map(checkbox => checkbox.value)
                .filter(field => field !== 'filter_oci');

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

        function checkMatrixDateSelection() {
            const hasMatrixDate = getSelectedMatrixDateFields().length > 0;
            const visualizationControls = document.getElementById('visualization-controls');

            if (hasMatrixDate) {
                visualizationControls.style.display = 'block';
            } else {
                visualizationControls.style.display = 'none';
                const listRadio = document.querySelector('input[name="view_type"][value="list"]');
                if (listRadio) listRadio.checked = true;
            }
        }

        // Open filter modal
        function openFilterModal() {
            const modal = document.getElementById('filter-modal');
            if (modal) {
                modal.classList.remove('hidden');
            } else {
                console.error('Modal de filtro não encontrado');
            }
        }

        // Close filter modal
        function closeFilterModal() {
            const modal = document.getElementById('filter-modal');
            if (modal) {
                modal.classList.add('hidden');
                resetFilterModal();
            }
        }

        // Reset filter modal
        function resetFilterModal() {
            const filterField = document.getElementById('filter-field');
            const filterOperator = document.getElementById('filter-operator');
            const filterValue = document.getElementById('filter-value');
            
            if (filterField) filterField.value = '';
            if (filterOperator) {
                filterOperator.value = '';
                filterOperator.innerHTML = '<option value="">Selecione um operador...</option>';
            }
            if (filterValue) filterValue.value = '';
        }

        // Populate filter field options
        function populateFilterFieldOptions() {
            const select = document.getElementById('filter-field');
            if (!select) {
                console.warn('Elemento filter-field não encontrado');
                return;
            }
            
            select.innerHTML = '<option value="">Selecione um campo...</option>';
            
            if (!availableFields || Object.keys(availableFields).length === 0) {
                return;
            }
            
            Object.keys(availableFields).forEach(fieldKey => {
                const field = availableFields[fieldKey];
                if (!field || !field.label) {
                    return;
                }
                
                const option = document.createElement('option');
                option.value = fieldKey;
                option.textContent = field.label;
                select.appendChild(option);
            });
        }

        // Update filter operators based on selected field
        function updateFilterOperators() {
            const filterField = document.getElementById('filter-field');
            const operatorSelect = document.getElementById('filter-operator');
            
            if (!filterField || !operatorSelect) {
                console.warn('Elementos do modal de filtro não encontrados');
                return;
            }
            
            const fieldKey = filterField.value;
            operatorSelect.innerHTML = '<option value="">Selecione um operador...</option>';
            
            if (fieldKey && availableFields && availableFields[fieldKey]) {
                const operators = availableFields[fieldKey].operators || [];
                const operatorLabels = {
                    '=': 'Igual a',
                    '>': 'Maior que',
                    '<': 'Menor que',
                    '>=': 'Maior ou igual',
                    '<=': 'Menor ou igual',
                    'like': 'Contém',
                    'starts_with': 'Inicia com',
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
            const filterField = document.getElementById('filter-field');
            const filterOperator = document.getElementById('filter-operator');
            const filterValue = document.getElementById('filter-value');
            
            if (!filterField || !filterOperator || !filterValue) {
                alert('Erro: elementos do formulário não encontrados.');
                return;
            }
            
            const field = filterField.value;
            const operator = filterOperator.value;
            const value = filterValue.value;
            
            if (!field || !operator || (!value && field !== 'filter_oci')) {
                alert('Por favor, preencha todos os campos do filtro.');
                return;
            }
            
            if (!availableFields || !availableFields[field]) {
                alert('Campo selecionado inválido.');
                return;
            }
            
            // Handle special OCI filter
            let filterValueFinal = value;
            if (field === 'filter_oci') {
                filterValueFinal = true;
            }
            
            const filter = {
                id: ++filterCounter,
                field: field,
                operator: operator,
                value: filterValueFinal,
                label: `${availableFields[field].label} ${operator} ${field === 'filter_oci' ? 'SIM' : value}`
            };
            
            appliedFilters.push(filter);
            renderFilters();
            closeFilterModal();
        }

        // Render applied filters
        function renderFilters() {
            const noFiltersMessage = document.getElementById('no-filters-message');
            const filtersList = document.getElementById('filters-list');
            
            if (appliedFilters.length === 0) {
                noFiltersMessage.style.display = 'block';
                filtersList.innerHTML = '';
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
            
            filtersList.innerHTML = filtersHtml;
        }

        // Remove filter
        function removeFilter(filterId) {
            appliedFilters = appliedFilters.filter(f => f.id !== filterId);
            renderFilters();
        }

        // Funções showLoading, hideLoading e showSQL já estão disponíveis via aliases do RelatoriosBase
        // Não precisam ser redefinidas aqui

        // Generate report
        async function generateReport(format) {
            if (selectedFields.length === 0) {
                alert('Por favor, selecione pelo menos um campo para o relatório.');
                return;
            }
            
            // Detectar tipo de visualização
            const viewType = document.querySelector('input[name="view_type"]:checked')?.value || 'list';
            const isMatrixView = viewType === 'matrix';
            
            // Validar se matriz é possível
            if (isMatrixView && getSelectedMatrixDateFields().length === 0) {
                alert('Para visualização em matriz, selecione "Data Competência" ou "Data Movimento" (apenas um deles).');
                return;
            }

            if (isMatrixView && getSelectedMatrixDateFields().length > 1) {
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
            
            // Check if OCI filter is enabled
            const ociFilterEnabled = document.getElementById('oci-filter').checked;
            const susPaulistaFilterEnabled = document.getElementById('sus-paulista-filter')?.checked;
            
            // Build filters array
            let allFilters = appliedFilters.map(f => ({
                field: f.field,
                operator: f.operator,
                value: f.value
            }));
            
            // Add OCI filter if enabled
            if (ociFilterEnabled) {
                allFilters.push({
                    field: 'filter_oci',
                    operator: '=',
                    value: true
                });
            }

            if (susPaulistaFilterEnabled) {
                allFilters.push({
                    field: 'filter_sus_paulista',
                    operator: '=',
                    value: true
                });
            }
            
            const payload = {
                fields: selectedFields,
                filters: allFilters,
                format: format
            };
            
            try {
                const controller = new AbortController();
                window.currentRequest = controller;
                
                // Escolher rota baseada no tipo de visualização
                const routeUrl = isMatrixView ? 
                    '{{ route("relatorios.apac.generate-matrix") }}' : 
                    '{{ route("relatorios.apac.generate") }}';
                
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
                        const { message, sql, bindings } = await handleReportHttpError(response);
                        showError(message, 'results-container', sql, bindings);
                        return;
                    }
                    
                    const data = await response.json();
                    
                    if (data.error) {
                        showError(data.error, 'results-container', data.sql, data.bindings);
                        return;
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
                    await handleFileDownload(response, format, `relatorio_apac.${format === 'excel' ? 'xlsx' : format}`);
                }
            } catch (error) {
                console.error('Error generating APAC report:', error);
                
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

        // Toggle visualization help
        function toggleVisualizationHelp() {
            const helpDiv = document.getElementById('visualization-help');
            if (helpDiv.style.display === 'none') {
                helpDiv.style.display = 'block';
            } else {
                helpDiv.style.display = 'none';
            }
        }

        // Funções renderResults e cancelSearch já definidas no módulo compartilhado - usando aliases acima
    </script>
@endsection