/**
 * Módulo JavaScript compartilhado para relatórios
 * Contém funções comuns para RelatorioController e RelatorioApacController
 */

const RelatoriosBase = (function () {
    'use strict';

    // Configuração padrão
    const defaultConfig = {
        buttonIds: ['generate-report', 'export-excel', 'export-pdf', 'export-csv'],
        loadingIndicatorId: 'loading-indicator',
        resultsContainerId: 'results-container',
        cancelSearchId: 'cancel-search',
        sqlPanelId: 'sql-panel',
        sqlDisplayId: 'sql-display'
    };

    const CURRENCY_FIELDS = new Set([
        'PRD_VL_P', 'PAP_VALOR', 'BPI_VL_P', 'cismetro_total', 'cismetro_valor',
        'sus_paulista_tab', 'sus_paulista_tab_total', 'sus_paulista_tsp', 'sus_paulista_tsp_total',
        'VALOR_TOTAL_AIH', 'VALOR_TOTAL', 'VALOR',
    ]);

    const INTEGER_FIELDS = new Set([
        'PRD_QT_P', 'PAP_QT_P', 'BPI_QT_P', 'qtd_aih', 'DIARIAS', 'DIARIAS_UTI',
    ]);

    function isAlreadyBrFormatted(value) {
        return typeof value === 'string' && (
            value.startsWith('R$') ||
            /^\d{1,3}(\.\d{3})*(,\d+)?$/.test(value)
        );
    }

    function formatBrInteger(value) {
        const num = Number(value);
        if (!Number.isFinite(num)) {
            return value ?? '';
        }

        return num.toLocaleString('pt-BR', { maximumFractionDigits: 0 });
    }

    function formatBrCurrency(value) {
        const num = Number(value);
        if (!Number.isFinite(num)) {
            return value ?? '';
        }

        return 'R$ ' + num.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatMatrixFieldValue(field, value, fieldMeta = null) {
        if (fieldMeta?.type === 'currency' || CURRENCY_FIELDS.has(field)) {
            return formatBrCurrency(value);
        }

        if (fieldMeta?.type === 'number' || INTEGER_FIELDS.has(field)) {
            return formatBrInteger(value);
        }

        const num = Number(value);
        if (Number.isFinite(num)) {
            return num.toLocaleString('pt-BR');
        }

        return value ?? '';
    }

    function escapeHtml(text) {
        return String(text ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatMatrixAxisList(items, separator = ' — ') {
        if (!items || items.length === 0) {
            return '—';
        }

        return items.map((item, index) => {
            const prefix = items.length > 1 ? `${index + 1}) ` : '';
            return `${prefix}${item.label}`;
        }).join(separator);
    }

    function renderMatrixMetaBanner(matrixMeta) {
        if (!matrixMeta) {
            return '';
        }

        const columns = formatMatrixAxisList(matrixMeta.columns);
        const rows = formatMatrixAxisList(matrixMeta.rows);
        const values = formatMatrixAxisList(matrixMeta.values);
        const split = matrixMeta.split
            ? `<div><span class="font-medium text-gray-800">Quebra:</span> ${escapeHtml(matrixMeta.split.label)}</div>`
            : '';

        return `
            <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-gray-700">
                <p class="mb-2 font-medium text-gray-900">Estrutura da matriz</p>
                <div class="grid gap-1 sm:grid-cols-1">
                    <div><span class="font-medium text-gray-800">Colunas:</span> ${escapeHtml(columns)}</div>
                    <div><span class="font-medium text-gray-800">Linhas:</span> ${escapeHtml(rows)}</div>
                    <div><span class="font-medium text-gray-800">Valores:</span> ${escapeHtml(values)}</div>
                    ${split}
                </div>
            </div>
        `;
    }

    function getMatrixRowHeaderLabel(matrixMeta) {
        if (!matrixMeta?.rows?.length) {
            return 'Categoria';
        }

        if (matrixMeta.rows.length === 1) {
            return matrixMeta.rows[0].label;
        }

        return formatMatrixAxisList(matrixMeta.rows, ' + ');
    }

    function formatMatrixCellValue(valuesMap, fieldMeta) {
        if (!fieldMeta) {
            return '-';
        }

        const value = valuesMap?.[fieldMeta.field] ?? 0;

        return formatMatrixFieldValue(fieldMeta.field, value, fieldMeta);
    }

    function normalizeMatrixValueFields(valueFields) {
        if (valueFields?.length) {
            return valueFields;
        }

        return [{ field: '_value', label: 'Valor', type: 'number' }];
    }

    function buildMatrixColumnGroups(competencias, valueFields) {
        const fields = normalizeMatrixValueFields(valueFields);
        const groups = competencias.map((comp) => ({
            type: 'period',
            comp,
            columns: fields.map((fieldMeta, index) => ({
                fieldMeta,
                order: index + 1,
            })),
        }));

        groups.push({
            type: 'total',
            label: 'Total',
            columns: fields.map((fieldMeta, index) => ({
                fieldMeta,
                order: index + 1,
            })),
        });

        return { fields, groups };
    }

    function renderMatrixTableHeaders(rowHeaderLabel, groups, multiValue, isMobile) {
        const cellClass = isMobile ? 'px-1 py-1 text-xs' : 'px-2 py-2 text-xs';
        let html = '<thead class="bg-gray-50 sticky top-0 z-20">';

        if (multiValue) {
            html += '<tr>';
            html += `<th rowspan="2" class="px-3 py-2 border text-left text-sm font-medium sticky-left bg-gray-50 z-30 min-w-[200px]">${rowHeaderLabel}</th>`;

            groups.forEach((group) => {
                const topLabel = group.type === 'period' ? group.comp.label : group.label;
                const topClass = group.type === 'total' ? ' bg-blue-50' : '';
                html += `<th colspan="${group.columns.length}" class="${cellClass} border text-center font-medium${topClass}">${escapeHtml(topLabel)}</th>`;
            });

            html += '</tr><tr>';

            groups.forEach((group) => {
                const isTotalGroup = group.type === 'total';
                group.columns.forEach((col, index) => {
                    const isLastTotal = isTotalGroup && index === group.columns.length - 1;
                    const extraClass = isTotalGroup ? ' bg-blue-50' : ' bg-gray-100/80';
                    const stickyClass = isLastTotal ? ' sticky-right' : '';
                    html += `<th class="${cellClass} border text-center font-medium${extraClass}${stickyClass}">${escapeHtml(`${col.order}) ${col.fieldMeta.label}`)}</th>`;
                });
            });

            html += '</tr>';
        } else {
            html += '<tr>';
            html += `<th class="px-3 py-2 border text-left text-sm font-medium sticky-left bg-gray-50 z-30 min-w-[200px]">${rowHeaderLabel}</th>`;

            groups.forEach((group) => {
                if (group.type === 'period') {
                    html += `<th class="${cellClass} border text-center font-medium min-w-[80px]">${escapeHtml(group.comp.label)}</th>`;
                } else {
                    html += `<th class="${cellClass} border text-center font-medium bg-blue-50 min-w-[100px] sticky-right">${escapeHtml(group.label)}</th>`;
                }
            });

            html += '</tr>';
        }

        html += '</thead>';

        return html;
    }

    function renderMatrixDataCells(valuesByPeriod, rowTotals, groups, options = {}) {
        const { variant = 'body' } = options;
        let html = '';

        groups.forEach((group) => {
            const isTotalGroup = group.type === 'total';

            group.columns.forEach((col, index) => {
                const source = isTotalGroup
                    ? rowTotals
                    : (valuesByPeriod?.[group.comp.code] || {});
                const formatted = formatMatrixCellValue(source, col.fieldMeta);
                const isLastTotal = isTotalGroup && index === group.columns.length - 1;

                let extraClass = '';
                if (isTotalGroup) {
                    const bgClass = variant === 'footer'
                        ? (isLastTotal ? 'bg-blue-200' : 'bg-blue-100')
                        : 'bg-blue-50';
                    const weightClass = variant === 'footer' ? 'font-bold' : 'font-semibold';
                    extraClass = ` ${weightClass} ${bgClass}${isLastTotal ? ' sticky-right' : ''}`;
                }

                html += `<td class="px-2 py-2 border text-xs text-center${extraClass}">${formatted}</td>`;
            });
        });

        return html;
    }

    function countMatrixDataColumns(groups) {
        return groups.reduce((total, group) => total + group.columns.length, 0);
    }

    function formatDisplayValue(value) {
        if (value === null || value === undefined || value === '') {
            return '';
        }

        if (isAlreadyBrFormatted(value)) {
            return value;
        }

        if (typeof value === 'number') {
            return Number.isInteger(value)
                ? formatBrInteger(value)
                : formatBrCurrency(value);
        }

        // Strings de dígitos puros são CÓDIGOS (CNES, SIGTAP, CID) — preservar como
        // texto (zeros à esquerda, sem separador de milhar). Números reais já chegam
        // pré-formatados do servidor (capturados por isAlreadyBrFormatted acima).
        return value;
    }

    /**
     * Show loading indicator
     */
    function showLoading(config = {}) {
        const cfg = { ...defaultConfig, ...config };

        const loadingIndicator = document.getElementById(cfg.loadingIndicatorId);
        if (loadingIndicator) {
            loadingIndicator.classList.remove('hidden');
        }

        const resultsContainer = document.getElementById(cfg.resultsContainerId);
        if (resultsContainer) {
            resultsContainer.innerHTML = '';
        }

        hideSqlPanel(cfg);

        const cancelSearch = document.getElementById(cfg.cancelSearchId);
        if (cancelSearch) {
            cancelSearch.classList.remove('hidden');
        }

        // Disable buttons
        cfg.buttonIds.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        });
    }

    /**
     * Hide loading indicator
     */
    function hideLoading(config = {}) {
        const cfg = { ...defaultConfig, ...config };

        const loadingIndicator = document.getElementById(cfg.loadingIndicatorId);
        if (loadingIndicator) {
            loadingIndicator.classList.add('hidden');
        }

        const cancelSearch = document.getElementById(cfg.cancelSearchId);
        if (cancelSearch) {
            cancelSearch.classList.add('hidden');
        }

        // Enable buttons
        cfg.buttonIds.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        });
    }

    /**
     * Show matrix-specific loading
     */
    function showMatrixLoading(config = {}) {
        const cfg = { ...defaultConfig, ...config };

        const loadingHtml = `
            <div class="flex items-center justify-center py-12">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Gerando Matriz por Competência</h3>
                    <p class="text-sm text-gray-600 mb-4">Processando dados e criando estrutura pivot...</p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-w-md mx-auto">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-blue-800">Matrizes podem levar mais tempo para processar grandes volumes de dados.</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const resultsContainer = document.getElementById(cfg.resultsContainerId);
        if (resultsContainer) {
            resultsContainer.innerHTML = loadingHtml;
        }

        hideSqlPanel(cfg);

        const cancelSearch = document.getElementById(cfg.cancelSearchId);
        if (cancelSearch) {
            cancelSearch.classList.remove('hidden');
        }

        // Disable buttons
        cfg.buttonIds.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        });
    }

    /**
     * Hide SQL panel until a report returns SQL
     */
    function hideSqlPanel(config = {}) {
        const cfg = { ...defaultConfig, ...config };
        const sqlPanel = document.getElementById(cfg.sqlPanelId);

        if (sqlPanel) {
            sqlPanel.style.display = 'none';
        }
    }

    /**
     * Prepare collapsible SQL panel (runs once per page)
     */
    function initSqlPanel(panel) {
        if (!panel || panel.dataset.sqlCollapsible === 'true') {
            return;
        }

        const header = panel.querySelector('h3');
        const content = panel.querySelector('#sql-panel-content')
            || panel.querySelector('.bg-gray-100');

        if (!header || !content) {
            return;
        }

        content.id = 'sql-panel-content';
        content.style.display = 'none';
        content.classList.add('mt-4');

        const title = header.textContent.trim() || 'SQL Gerado';
        header.className = 'text-lg font-medium text-gray-900 mb-0 cursor-pointer select-none flex items-center justify-between gap-3';
        header.innerHTML = `
            <span>${escapeHtml(title)}</span>
            <span id="sql-panel-toggle" class="text-sm font-normal text-blue-600 whitespace-nowrap">clique para exibir</span>
        `;

        header.addEventListener('click', () => toggleSqlPanel());
        panel.dataset.sqlCollapsible = 'true';
    }

    function toggleSqlPanel(config = {}) {
        const cfg = { ...defaultConfig, ...config };
        const panel = document.getElementById(cfg.sqlPanelId);
        const content = document.getElementById('sql-panel-content');
        const toggle = document.getElementById('sql-panel-toggle');

        if (!content) {
            return;
        }

        const isOpen = content.style.display !== 'none';
        content.style.display = isOpen ? 'none' : 'block';

        if (toggle) {
            toggle.textContent = isOpen ? 'clique para exibir' : 'clique para ocultar';
        }

        if (panel) {
            panel.dataset.sqlOpen = isOpen ? 'false' : 'true';
        }
    }

    /**
     * Show SQL (collapsed by default; click header to expand)
     */
    function showSQL(sql, bindings, config = {}) {
        const cfg = { ...defaultConfig, ...config };

        const sqlPanel = document.getElementById(cfg.sqlPanelId);
        const sqlDisplay = document.getElementById(cfg.sqlDisplayId);

        if (!sqlPanel || !sqlDisplay) {
            return;
        }

        initSqlPanel(sqlPanel);

        let formattedSQL = sql;
        if (bindings && bindings.length > 0) {
            formattedSQL += '\n\nBindings: ' + JSON.stringify(bindings, null, 2);
        }

        sqlDisplay.textContent = formattedSQL;
        sqlPanel.style.display = 'block';

        const content = document.getElementById('sql-panel-content');
        const toggle = document.getElementById('sql-panel-toggle');

        if (content) {
            content.style.display = 'none';
        }

        if (toggle) {
            toggle.textContent = 'clique para exibir';
        }

        sqlPanel.dataset.sqlOpen = 'false';
    }

    /**
     * Load lookup data for a field
     */
    async function loadLookupData(field, search = '', lookupUrl) {
        try {
            const url = new URL(lookupUrl);
            url.searchParams.append('field', field);
            if (search) url.searchParams.append('search', search);

            const response = await fetch(url);
            return await response.json();
        } catch (error) {
            console.error('Error loading lookup data:', error);
            return [];
        }
    }

    /**
     * Render list results (original format)
     */
    function renderListResults(data, container) {
        if (!data.data || data.data.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum resultado encontrado com os filtros aplicados.</p>';
            return;
        }

        // Get headers from first row. Alinha à direita apenas colunas numéricas
        // (valores já BR-formatados pelo servidor: "1.234", "R$ ...").
        const firstRow = data.data[0];
        const keys = Object.keys(firstRow);
        const alignRight = keys.map(k => isAlreadyBrFormatted(firstRow[k]));

        const headers = keys.map((header, i) =>
            `<th class="px-2 py-1 border ${alignRight[i] ? 'text-right' : 'text-left'} text-xs font-medium">${header}</th>`
        ).join('');

        const rows = data.data.map(row => {
            const cells = Object.values(row).map((value, i) =>
                `<td class="px-2 py-1 border text-xs ${alignRight[i] ? 'text-right' : 'text-left'}">${formatDisplayValue(value)}</td>`
            ).join('');
            return `<tr>${cells}</tr>`;
        }).join('');

        let totalsHtml = '';
        if (data.totals && Object.keys(data.totals).length > 0) {
            const totalRows = Object.entries(data.totals).map(([label, value]) =>
                `<tr class="bg-blue-50"><td class="px-2 py-1 border font-semibold text-xs">${label}</td><td class="px-2 py-1 border font-semibold text-xs">${formatDisplayValue(value)}</td></tr>`
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

    /**
     * Render matrix results (pivot table format)
     */
    /**
     * Render matrix results (pivot table format)
     */
    function resolveMatrixValueFields(matrixMeta, matrixData) {
        if (matrixMeta?.values?.length) {
            return matrixMeta.values;
        }

        const sampleValues = matrixData.rows?.[0]?.values
            ? Object.values(matrixData.rows[0].values)[0]
            : matrixData.grand_totals;

        if (!sampleValues) {
            return [];
        }

        return Object.keys(sampleValues).map((field) => ({
            field,
            label: field,
            type: 'number',
        }));
    }

    function renderMatrixResults(matrixData, container, matrixMeta = null) {
        if (!matrixData.competencias || matrixData.competencias.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhuma competência encontrada para matriz.</p>';
            return;
        }

        const metaBanner = renderMatrixMetaBanner(matrixMeta);
        const valueFields = resolveMatrixValueFields(matrixMeta, matrixData);

        // Múltiplas tabelas: quebra por prestador, tipo de relatório, etc.
        if (matrixData.prestadores && Object.keys(matrixData.prestadores).length > 0) {
            let html = metaBanner;
            const splitLabels = {
                prd_uid: 'Prestador',
                tipo_relatorio: 'Tipo de Relatório',
                CNES: 'Prestador (CNES)',
            };
            const splitLabel = splitLabels[matrixData.split_field]
                || matrixMeta?.split?.label
                || 'Grupo';

            Object.values(matrixData.prestadores).forEach(sectionData => {
                const sectionTitle = sectionData.nome
                    ? `${splitLabel}: ${sectionData.nome}`
                    : splitLabel;

                html += `
                    <div class="mb-8 border-b-4 border-blue-200 pb-8 last:border-0 last:pb-0">
                        <h3 class="text-lg font-bold text-gray-800 mb-3 px-1 border-l-4 border-blue-500 pl-2">
                            ${escapeHtml(sectionTitle)}
                        </h3>
                        ${renderMatrixTable(sectionData, matrixMeta, valueFields, sectionTitle)}
                    </div>
                `;
            });

            container.innerHTML = html;
        } else {
            container.innerHTML = metaBanner + renderMatrixTable(matrixData, matrixMeta, valueFields);
        }
    }

    /**
     * Helper to build HTML for a single matrix table 
     */
    function renderMatrixTable(matrixData, matrixMeta = null, valueFields = [], sectionTitle = null) {
        const isLargeMatrix = matrixData.competencias.length > 12 || (matrixData.rows && matrixData.rows.length > 50);
        const isMobile = window.innerWidth < 768;
        const rowHeaderLabel = escapeHtml(getMatrixRowHeaderLabel(matrixMeta));
        const columnLabel = matrixMeta?.columns?.[0]?.label || 'Competência';
        const { fields, groups } = buildMatrixColumnGroups(matrixData.competencias, valueFields);
        const multiValue = fields.length > 1;
        const dataColumnCount = countMatrixDataColumns(groups);
        const sectionHint = sectionTitle
            ? `<p class="text-xs text-gray-500 mt-1">${escapeHtml(sectionTitle)}</p>`
            : '';

        let matrixHtml = `
            <div class="mb-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-600">
                        <strong>Visualização:</strong> ${matrixData.rows ? matrixData.rows.length : 0} categorias × ${matrixData.competencias.length} ${escapeHtml(columnLabel.toLowerCase())}
                    </p>
                    ${sectionHint}
                    ${isLargeMatrix ? '<p class="text-xs text-amber-600 mt-1">⚠️ Matriz grande - use scroll horizontal para navegar</p>' : ''}
                </div>
            </div>
            <div class="matrix-container ${isMobile ? 'mobile-matrix' : ''}" style="max-height: 70vh; overflow: auto;">
                <table class="min-w-full border-collapse border border-gray-300 matrix-table">
                    ${renderMatrixTableHeaders(rowHeaderLabel, groups, multiValue, isMobile)}
                    <tbody>
        `;

        if (matrixData.rows && matrixData.rows.length > 0) {
            matrixData.rows.forEach((row) => {
                matrixHtml += '<tr class="hover:bg-gray-50">';
                matrixHtml += `<td class="px-3 py-2 border text-sm font-medium sticky-left bg-white text-left">${row.category}</td>`;
                matrixHtml += renderMatrixDataCells(row.values, row.totals, groups);
                matrixHtml += '</tr>';
            });
        } else {
            matrixHtml += `<tr><td colspan="${dataColumnCount + 1}" class="px-3 py-4 text-center text-gray-500">Nenhum dado para este grupo</td></tr>`;
        }

        matrixHtml += '<tr class="bg-blue-100 font-semibold">';
        matrixHtml += '<td class="px-3 py-2 border text-sm sticky-left bg-blue-100 text-left">Total</td>';
        matrixHtml += renderMatrixDataCells(matrixData.totals, matrixData.grand_totals, groups, { variant: 'footer' });
        matrixHtml += '</tr></tbody></table></div>';

        // Add CSS for responsive matrix
        matrixHtml += `
            <style>
            .matrix-table {
                border-spacing: 0;
                font-size: 0.75rem;
            }
            .matrix-table .sticky-left {
                position: sticky;
                left: 0;
                z-index: 30;
                box-shadow: 2px 0 5px rgba(0,0,0,0.1);
                background-color: white !important;
            }
            .matrix-table .sticky-right {
                position: sticky;
                right: 0;
                z-index: 20;
                box-shadow: -2px 0 5px rgba(0,0,0,0.1);
            }
            .matrix-container {
                border: 1px solid #d1d5db;
                border-radius: 0.5rem;
                background: white;
            }
            .mobile-matrix .matrix-table {
                font-size: 0.625rem;
            }
            .mobile-matrix .sticky-left {
                min-width: 150px !important;
            }
            .matrix-table tbody tr:hover {
                background-color: #f9fafb;
            }
            .matrix-table th {
                background-color: #f3f4f6 !important;
                font-weight: 600;
            }
            </style>
        `;

        return matrixHtml;
    }

    /**
     * Render results (dispatches to list or matrix renderer)
     */
    function renderResults(data, containerId = 'results-container') {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error('Results container not found:', containerId);
            return;
        }

        if (!data.data || (Array.isArray(data.data) && data.data.length === 0) ||
            (data.type === 'matrix' &&
                (!data.data.rows || data.data.rows.length === 0) &&
                (!data.data.prestadores || Object.keys(data.data.prestadores).length === 0))) {
            container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum resultado encontrado com os filtros aplicados.</p>';
            return;
        }

        // Check if it's matrix data
        if (data.type === 'matrix') {
            renderMatrixResults(data.data, container, data.meta ?? null);
        } else {
            renderListResults(data, container);
        }
    }

    /**
     * Cancel search
     */
    function cancelSearch() {
        if (window.currentRequest) {
            window.currentRequest.abort();
            window.currentRequest = null;
        }
        hideLoading();
    }

    /**
     * Show error message (optionally with SQL debug info)
     */
    function showError(message, containerId = 'results-container', sql = null, bindings = null) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = `
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
                            <p>${message}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        if (sql) {
            showSQL(sql, bindings);
        }
    }

    /**
     * Parse error HTTP response and surface SQL when available
     */
    async function handleReportHttpError(response) {
        let message = `Erro do servidor: ${response.status}`;
        let sql = null;
        let bindings = null;

        const errorText = await response.text();
        console.error('Server error:', errorText);

        try {
            const errorData = JSON.parse(errorText);
            if (errorData.error) {
                message = errorData.error;
            }
            if (/max_statement_time exceeded/i.test(message)) {
                message = 'Tempo de execução excedido (timeout). A consulta é muito '
                    + 'pesada. Veja o SQL gerado abaixo.';
            }
            sql = errorData.sql ?? null;
            bindings = errorData.bindings ?? null;
        } catch (e) {
            // Resposta não-JSON = erro fatal (ex.: timeout de execução). O SQL não
            // volta nesse caso; fica registrado no log do servidor (laravel.log).
            if (/Maximum execution time/i.test(errorText)) {
                message = 'Tempo de execução excedido (timeout). A consulta é muito '
                    + 'pesada. O SQL gerado foi registrado no log do servidor.';
            } else {
                message = `Erro do servidor: ${response.status}. `
                    + 'O SQL gerado foi registrado no log do servidor.';
            }
        }

        return { message, sql, bindings };
    }

    /**
     * Show cancellation message
     */
    function showCancellation(containerId = 'results-container') {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = `
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
    }

    /**
     * Handle file download
     */
    async function handleFileDownload(response, format, filename = null) {
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
        a.download = filename || `relatorio.${format === 'excel' ? 'xlsx' : format}`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }

    // Public API
    return {
        showLoading,
        hideLoading,
        showMatrixLoading,
        showSQL,
        loadLookupData,
        renderResults,
        renderListResults,
        renderMatrixResults,
        cancelSearch,
        showError,
        showCancellation,
        handleReportHttpError,
        handleFileDownload,
        defaultConfig
    };
})();

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RelatoriosBase;
}

// Make available globally
window.RelatoriosBase = RelatoriosBase;

