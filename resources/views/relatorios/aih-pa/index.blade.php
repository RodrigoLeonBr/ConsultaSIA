@extends('layouts.modern')

@section('title', 'Relatório de Procedimentos AIH')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Relatório de Procedimentos AIH</h1>
        <p class="text-gray-600 mt-1">Procedimentos detalhados por internação (tabela s_aih_pa)</p>
    </div>
    <a href="{{ route('relatorios.aih.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        ← Resumo AIH
    </a>
</div>
@endsection

@section('content')
    <style>
        #add-filter { min-width: 140px; white-space: nowrap; }
    </style>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Procedimentos por AIH (SIHD — TB_HPA)</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Detalha os <strong>procedimentos realizados</strong> por internação (tabela <code>s_aih_pa</code>).</p>
                        <p class="mt-1">Para o resumo das internações:
                            <a href="{{ route('relatorios.aih.index') }}" class="font-medium text-blue-800 underline">Relatório AIH →</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-1">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Seleção de Campos</h3>
                        <div class="space-y-2 max-h-96 overflow-y-auto border rounded-lg p-4">
                            <div id="field-checkboxes">
                                <div id="fields-loading" class="flex items-center justify-center py-4">
                                    <svg class="animate-spin h-5 w-5 text-blue-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-sm text-gray-600">Carregando campos...</span>
                                </div>
                                <div id="fields-error" class="hidden"></div>
                                <div id="fields-retry" class="hidden mt-2">
                                    <button onclick="loadAvailableFields()" class="text-sm text-blue-600 hover:text-blue-800 underline">Tentar novamente</button>
                                </div>
                            </div>
                        </div>
                    </div>

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
                        <div class="min-h-32 border rounded-lg p-4">
                            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                                <label class="flex items-center">
                                    <input type="checkbox" id="sus-paulista-filter" class="mr-2 rounded border-gray-300">
                                    <span class="text-sm font-medium text-emerald-800">
                                        Somente procedimentos com Tabela SUS Paulista (SIH)
                                    </span>
                                </label>
                                <p class="text-xs text-emerald-600 mt-1">Oculta linhas sem cadastro vigente na tabela SUS Paulista (modalidade hospitalar) para a competência.</p>
                            </div>
                            <p class="text-gray-500 text-sm" id="no-filters-message">Nenhum filtro adicionado.</p>
                            <div id="filters-list" class="space-y-3 mt-3"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-gray-50 rounded-lg" id="visualization-controls" style="display: none;">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Tipo de Visualização</h4>
                    <div class="flex flex-col sm:flex-row sm:space-x-6 space-y-2 sm:space-y-0">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="view_type" value="list" checked class="form-radio text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">Lista Simples</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="view_type" value="matrix" class="form-radio text-blue-600">
                            <span class="ml-2 text-sm text-gray-700">Matriz por Competência</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button id="generate-report" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">🔍 Gerar Relatório Procedimentos AIH</button>
                    <button id="cancel-search" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 hidden">❌ Cancelar</button>
                    <button id="export-excel" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">📊 Exportar Excel</button>
                    <button id="export-pdf" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">📄 Exportar PDF</button>
                    <button id="export-csv" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">📋 Exportar CSV</button>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6" id="sql-panel" style="display: none;">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">SQL Gerado</h3>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <pre id="sql-display" class="text-sm text-gray-800 whitespace-pre-wrap"></pre>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Resultados</h3>
                <div id="loading-indicator" class="hidden text-center py-8">
                    <div class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-gray-600">Processando procedimentos AIH...</span>
                    </div>
                </div>
                <div id="results-container">
                    <p class="text-gray-500 text-center py-8">Configure os campos e filtros acima e clique em "Gerar Relatório Procedimentos AIH".</p>
                </div>
            </div>
        </div>
    </div>

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
                            <label id="filter-value-label" class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                            <input type="text" id="filter-value" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Digite o valor...">
                            <div id="filter-value2-container" class="hidden mt-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Até (fim da faixa)</label>
                                <input type="text" id="filter-value2" class="w-full border border-gray-300 rounded-md px-3 py-2" placeholder="Valor final...">
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button id="cancel-filter" class="px-4 py-2 text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50">Cancelar</button>
                        <button id="save-filter" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Adicionar Filtro</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/relatorios-base.js') }}"></script>
    <script>
        let availableFields = {};
        let selectedFields  = [];
        let appliedFilters  = [];
        let filterCounter   = 0;
        const COMPETENCIA_FIELD  = 'COMPETENCIA';
        const MATRIX_DATE_FIELDS = [COMPETENCIA_FIELD];

        const showLoading        = RelatoriosBase.showLoading;
        const hideLoading        = RelatoriosBase.hideLoading;
        const showMatrixLoading  = RelatoriosBase.showMatrixLoading;
        const showSQL            = RelatoriosBase.showSQL;
        const renderResults      = RelatoriosBase.renderResults;
        const cancelSearch       = RelatoriosBase.cancelSearch;
        const showError          = RelatoriosBase.showError;
        const showCancellation   = RelatoriosBase.showCancellation;
        const handleFileDownload = RelatoriosBase.handleFileDownload;
        const loadLookupData     = (field, search) =>
            RelatoriosBase.loadLookupData(field, search, '{{ route("relatorios.aih-pa.lookup") }}');

        async function loadAvailableFields() {
            const loading  = document.getElementById('fields-loading');
            const errorDiv = document.getElementById('fields-error');
            const retryDiv = document.getElementById('fields-retry');
            try {
                loading?.classList.remove('hidden');
                errorDiv?.classList.add('hidden');
                retryDiv?.classList.add('hidden');
                const response = await fetch('{{ route("relatorios.aih-pa.fields") }}');
                if (!response.ok) throw new Error('HTTP ' + response.status);
                const data = await response.json();
                if (!data || !data.fields) throw new Error('Resposta inválida');
                availableFields = data.fields;
                loading?.classList.add('hidden');
                renderFieldCheckboxes();
                populateFilterFieldOptions();
            } catch (err) {
                loading?.classList.add('hidden');
                if (errorDiv) {
                    errorDiv.classList.remove('hidden');
                    const p = document.createElement('p');
                    p.className = 'text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg p-3';
                    p.textContent = 'Erro ao carregar campos: ' + err.message;
                    errorDiv.replaceChildren(p);
                }
                retryDiv?.classList.remove('hidden');
            }
        }
        window.loadAvailableFields = loadAvailableFields;

        document.addEventListener('DOMContentLoaded', () => {
            loadAvailableFields().then(() => setupEventListeners()).catch(() => setupEventListeners());
        });

        function setupEventListeners() {
            document.addEventListener('change', e => {
                if (e.target.classList.contains('field-checkbox')) updateSelectedFields();
            });
            document.getElementById('add-filter')?.addEventListener('click', openFilterModal);
            document.getElementById('cancel-filter')?.addEventListener('click', closeFilterModal);
            document.getElementById('save-filter')?.addEventListener('click', doSaveFilter);
            document.getElementById('filter-field')?.addEventListener('change', updateFilterOperators);
            document.getElementById('filter-operator')?.addEventListener('change', function() {
                updateValueContainer(this.value);
            });
            document.getElementById('generate-report')?.addEventListener('click', () => generateReport('html'));
            document.getElementById('cancel-search')?.addEventListener('click', cancelSearch);
            document.getElementById('export-excel')?.addEventListener('click', () => generateReport('excel'));
            document.getElementById('export-pdf')?.addEventListener('click', () => generateReport('pdf'));
            document.getElementById('export-csv')?.addEventListener('click', () => generateReport('csv'));
        }

        function updateValueContainer(operator) {
            const lbl  = document.getElementById('filter-value-label');
            const v1   = document.getElementById('filter-value');
            const v2c  = document.getElementById('filter-value2-container');
            const v2   = document.getElementById('filter-value2');
            if (operator === 'between') {
                if (lbl) lbl.textContent = 'De (início da faixa)';
                if (v1)  v1.placeholder  = 'Número AIH inicial...';
                v2c?.classList.remove('hidden');
                if (v2)  v2.placeholder  = 'Número AIH final...';
            } else if (operator === 'pattern') {
                if (lbl) lbl.textContent = 'Padrão (? = qualquer caractere)';
                if (v1)  v1.placeholder  = 'Ex: ????50??????? (5º e 6º char = 50)';
                v2c?.classList.add('hidden');
            } else {
                if (lbl) lbl.textContent = 'Valor';
                if (v1)  v1.placeholder  = 'Digite o valor...';
                v2c?.classList.add('hidden');
            }
        }

        function renderFieldCheckboxes() {
            const container = document.getElementById('field-checkboxes');
            Array.from(container.children).forEach(c => {
                if (!['fields-loading','fields-error','fields-retry'].includes(c.id)) c.remove();
            });
            const frag = document.createDocumentFragment();
            Object.keys(availableFields).forEach(key => {
                const field = availableFields[key];
                if (!field?.label) return;
                const wrapper = document.createElement('div');
                wrapper.className = 'flex items-center py-1';
                const cb = document.createElement('input');
                cb.type = 'checkbox'; cb.id = 'field-' + key; cb.value = key;
                cb.className = 'field-checkbox mr-2 rounded border-gray-300 text-blue-600';
                const lbl = document.createElement('label');
                lbl.htmlFor = 'field-' + key;
                lbl.className = 'text-sm text-gray-700 cursor-pointer';
                lbl.textContent = field.label;
                wrapper.appendChild(cb); wrapper.appendChild(lbl);
                frag.appendChild(wrapper);
            });
            container.appendChild(frag);
        }

        function updateSelectedFields() {
            selectedFields = Array.from(document.querySelectorAll('.field-checkbox:checked')).map(cb => cb.value);
            const hasDate = MATRIX_DATE_FIELDS.some(f => selectedFields.includes(f));
            document.getElementById('visualization-controls').style.display = hasDate ? 'block' : 'none';
        }

        function openFilterModal()  { document.getElementById('filter-modal')?.classList.remove('hidden'); }
        function closeFilterModal() { document.getElementById('filter-modal')?.classList.add('hidden'); resetFilterModal(); }
        function resetFilterModal() {
            const ff  = document.getElementById('filter-field');
            const fo  = document.getElementById('filter-operator');
            const fv  = document.getElementById('filter-value');
            const fv2 = document.getElementById('filter-value2');
            const lbl = document.getElementById('filter-value-label');
            const v2c = document.getElementById('filter-value2-container');
            if (ff)  ff.value  = '';
            if (fo)  { fo.value = ''; fo.replaceChildren(new Option('Selecione um operador...', '')); }
            if (fv)  { fv.value = ''; fv.placeholder = 'Digite o valor...'; }
            if (fv2) fv2.value  = '';
            if (lbl) lbl.textContent = 'Valor';
            v2c?.classList.add('hidden');
        }

        function populateFilterFieldOptions() {
            const select = document.getElementById('filter-field');
            if (!select) return;
            select.replaceChildren(new Option('Selecione um campo...', ''));
            Object.keys(availableFields).forEach(key => {
                const field = availableFields[key];
                if (!field?.label) return;
                select.appendChild(new Option(field.label, key));
            });
        }

        function updateFilterOperators() {
            const opSelect = document.getElementById('filter-operator');
            const fieldKey = document.getElementById('filter-field')?.value;
            if (!opSelect) return;
            opSelect.replaceChildren(new Option('Selecione um operador...', ''));
            updateValueContainer('');
            const labels = { '=':'Igual a','>':'Maior que','<':'Menor que','>=':'Maior ou igual','<=':'Menor ou igual','like':'Contém','starts_with':'Inicia com','between':'Faixa (início → fim)','in':'Em lista','pattern':'Padrão (? = qualquer char)' };
            if (fieldKey && availableFields[fieldKey]) {
                (availableFields[fieldKey].operators || []).forEach(op => opSelect.appendChild(new Option(labels[op] || op, op)));
            }
        }

        function doSaveFilter() {
            const field    = document.getElementById('filter-field')?.value;
            const operator = document.getElementById('filter-operator')?.value;
            const v1       = (document.getElementById('filter-value')?.value || '').trim();
            const v2       = (document.getElementById('filter-value2')?.value || '').trim();
            const fieldLabel = availableFields[field]?.label || field;

            if (!field || !operator) { alert('Selecione campo e operador.'); return; }

            let value, label;
            if (operator === 'between') {
                if (!v1 || !v2) { alert('Preencha o valor inicial e final da faixa.'); return; }
                value = [v1, v2];
                label = fieldLabel + ': de ' + v1 + ' até ' + v2;
            } else if (operator === 'pattern') {
                if (!v1) { alert('Preencha o padrão do filtro.'); return; }
                value = v1;
                label = fieldLabel + ' padrão: ' + v1;
            } else {
                if (!v1) { alert('Preencha o valor do filtro.'); return; }
                value = v1;
                const opLabels = { '=':'=','>':'>','<':'<','>=':'>=','<=':'<=','like':'contém','starts_with':'inicia com','ends_with':'termina em' };
                label = fieldLabel + ' ' + (opLabels[operator] || operator) + ' ' + v1;
            }

            appliedFilters.push({ id: ++filterCounter, field, operator, value, label });
            renderFilters();
            closeFilterModal();
        }

        function renderFilters() {
            const noMsg = document.getElementById('no-filters-message');
            const list  = document.getElementById('filters-list');
            if (appliedFilters.length === 0) { noMsg.style.display = 'block'; list.replaceChildren(); return; }
            noMsg.style.display = 'none';
            list.replaceChildren();
            appliedFilters.forEach(f => {
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between bg-blue-50 border border-blue-200 rounded-lg p-3';
                const span = document.createElement('span');
                span.className = 'text-sm text-blue-800';
                span.textContent = f.label;
                const btn = document.createElement('button');
                btn.className = 'text-red-600 hover:text-red-800 text-sm';
                btn.textContent = '✕ Remover';
                btn.addEventListener('click', () => { appliedFilters = appliedFilters.filter(x => x.id !== f.id); renderFilters(); });
                row.appendChild(span); row.appendChild(btn);
                list.appendChild(row);
            });
        }

        async function generateReport(format) {
            if (selectedFields.length === 0) { alert('Selecione pelo menos um campo.'); return; }
            const viewType   = document.querySelector('input[name="view_type"]:checked')?.value || 'list';
            const isMatrix   = viewType === 'matrix';
            const matrixDate = MATRIX_DATE_FIELDS.find(f => selectedFields.includes(f));
            if (isMatrix && !matrixDate) { alert('Para matriz selecione o campo Competência.'); return; }
            isMatrix ? showMatrixLoading() : showLoading();

            const payload   = {
                fields: selectedFields,
                filters: (() => {
                    let allFilters = appliedFilters.map(f => ({ field: f.field, operator: f.operator, value: f.value }));
                    if (document.getElementById('sus-paulista-filter')?.checked) {
                        allFilters.push({ field: 'filter_sus_paulista', operator: '=', value: true });
                    }
                    return allFilters;
                })(),
                format
            };
            const routeUrl  = isMatrix ? '{{ route("relatorios.aih-pa.generate-matrix") }}' : '{{ route("relatorios.aih-pa.generate") }}';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const controller = new AbortController();
                window.currentRequest = controller;
                const response = await fetch(routeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify(payload),
                    signal: controller.signal
                });
                if (format === 'html') {
                    if (!response.ok) throw new Error('Erro do servidor: ' + response.status);
                    const data = await response.json();
                    if (data.error) throw new Error(data.error);
                    if (data.sql) showSQL(data.sql, data.bindings);
                    renderResults(data);
                    if (data.type === 'matrix') hideLoading();
                } else {
                    if (!response.ok) throw new Error('Erro ao exportar: ' + response.status);
                    await handleFileDownload(response, format, 'relatorio_aih_pa.' + (format === 'excel' ? 'xlsx' : format));
                }
            } catch (err) {
                err.name === 'AbortError' ? showCancellation() : showError(err.message);
            } finally {
                hideLoading();
                window.currentRequest = null;
            }
        }
    </script>
@endsection
