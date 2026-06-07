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
     * Show SQL
     */
    function showSQL(sql, bindings, config = {}) {
        const cfg = { ...defaultConfig, ...config };

        const sqlPanel = document.getElementById(cfg.sqlPanelId);
        const sqlDisplay = document.getElementById(cfg.sqlDisplayId);

        if (!sqlPanel || !sqlDisplay) {
            return;
        }

        let formattedSQL = sql;
        if (bindings && bindings.length > 0) {
            formattedSQL += '\n\nBindings: ' + JSON.stringify(bindings, null, 2);
        }

        sqlDisplay.textContent = formattedSQL;
        sqlPanel.style.display = 'block';
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

        // Get headers from first row
        const firstRow = data.data[0];
        const headers = Object.keys(firstRow).map(header =>
            `<th class="px-2 py-1 border text-left text-xs font-medium">${header}</th>`
        ).join('');

        const rows = data.data.map(row => {
            const cells = Object.values(row).map(value =>
                `<td class="px-2 py-1 border text-xs">${value || ''}</td>`
            ).join('');
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

    /**
     * Render matrix results (pivot table format)
     */
    /**
     * Render matrix results (pivot table format)
     */
    function renderMatrixResults(matrixData, container) {
        if (!matrixData.competencias || matrixData.competencias.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhuma competência encontrada para matriz.</p>';
            return;
        }

        // Múltiplas tabelas: quebra por prestador, tipo de relatório, etc.
        if (matrixData.prestadores && Object.keys(matrixData.prestadores).length > 0) {
            let html = '';
            const splitLabels = {
                prd_uid: 'Prestador',
                tipo_relatorio: 'Tipo de Relatório'
            };
            const splitLabel = splitLabels[matrixData.split_field] || 'Grupo';

            Object.values(matrixData.prestadores).forEach(sectionData => {
                html += `
                    <div class="mb-8 border-b-4 border-blue-200 pb-8 last:border-0 last:pb-0">
                        <h3 class="text-lg font-bold text-gray-800 mb-3 px-1 border-l-4 border-blue-500 pl-2">
                            ${splitLabel}: ${sectionData.nome || 'Sem nome'}
                        </h3>
                        ${renderMatrixTable(sectionData)}
                    </div>
                `;
            });

            container.innerHTML = html;
        } else {
            // Standard single matrix render
            container.innerHTML = renderMatrixTable(matrixData);
        }
    }

    /**
     * Helper to build HTML for a single matrix table 
     */
    function renderMatrixTable(matrixData) {
        // Check if it's a large matrix for responsive handling
        const isLargeMatrix = matrixData.competencias.length > 12 || (matrixData.rows && matrixData.rows.length > 50);
        const isMobile = window.innerWidth < 768;

        // Build matrix info and controls
        let matrixHtml = `
            <div class="mb-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-600">
                        <strong>Visualização:</strong> ${matrixData.rows ? matrixData.rows.length : 0} categorias × ${matrixData.competencias.length} competências
                    </p>
                    ${isLargeMatrix ? '<p class="text-xs text-amber-600 mt-1">⚠️ Matriz grande - use scroll horizontal para navegar</p>' : ''}
                </div>
            </div>
            <div class="matrix-container ${isMobile ? 'mobile-matrix' : ''}" style="max-height: 70vh; overflow: auto;">
                <table class="min-w-full border-collapse border border-gray-300 matrix-table">
                    <thead class="bg-gray-50 sticky top-0 z-20">
                        <tr>
                            <th class="px-3 py-2 border text-left text-sm font-medium sticky-left bg-gray-50 z-30 min-w-[200px]">Categoria</th>
        `;

        // Add competencia headers with responsive sizing
        matrixData.competencias.forEach(comp => {
            const headerClass = isMobile ? 'px-1 py-2' : 'px-2 py-2';
            const textClass = isMobile ? 'text-xs' : 'text-xs';
            matrixHtml += `<th class="${headerClass} border text-center ${textClass} font-medium min-w-[80px]">${comp.label}</th>`;
        });
        matrixHtml += `<th class="px-2 py-2 border text-center text-xs font-medium bg-blue-50 min-w-[100px] sticky-right">Total</th></tr></thead><tbody>`;

        // Add data rows
        if (matrixData.rows && matrixData.rows.length > 0) {
            matrixData.rows.forEach(row => {
                matrixHtml += `<tr class="hover:bg-gray-50">`;
                matrixHtml += `<td class="px-3 py-2 border text-sm font-medium sticky-left bg-white text-left">${row.category}</td>`;

                // Add values for each competencia
                matrixData.competencias.forEach(comp => {
                    const values = row.values[comp.code] || {};
                    let cellContent = '';

                    // Format numeric values
                    Object.keys(values).forEach(field => {
                        const value = values[field] || 0;
                        if (field === 'PRD_QT_P' || field === 'PAP_QT_P') {
                            cellContent += value.toLocaleString('pt-BR');
                        } else if (field === 'PRD_VL_P' || field === 'PAP_VALOR') {
                            cellContent += 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                        } else {
                            cellContent += value.toLocaleString('pt-BR');
                        }
                        cellContent += '<br>';
                    });

                    if (!cellContent) cellContent = '-';
                    matrixHtml += `<td class="px-2 py-2 border text-xs text-center">${cellContent}</td>`;
                });

                // Add row total
                let totalContent = '';
                if (row.totals) {
                    Object.keys(row.totals).forEach(field => {
                        const value = row.totals[field] || 0;
                        if (field === 'PRD_QT_P' || field === 'PAP_QT_P') {
                            totalContent += value.toLocaleString('pt-BR');
                        } else if (field === 'PRD_VL_P' || field === 'PAP_VALOR') {
                            totalContent += 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                        } else {
                            totalContent += value.toLocaleString('pt-BR');
                        }
                        totalContent += '<br>';
                    });
                }
                matrixHtml += `<td class="px-2 py-2 border text-xs text-center font-semibold bg-blue-50">${totalContent || '-'}</td>`;
                matrixHtml += `</tr>`;
            });
        } else {
            const colspan = matrixData.competencias.length + 2;
            matrixHtml += `<tr><td colspan="${colspan}" class="px-3 py-4 text-center text-gray-500">Nenhum dado para este grupo</td></tr>`;
        }

        // Add totals row
        matrixHtml += `<tr class="bg-blue-100 font-semibold">`;
        matrixHtml += `<td class="px-3 py-2 border text-sm sticky-left bg-blue-100 text-left">Total</td>`;

        matrixData.competencias.forEach(comp => {
            const totals = matrixData.totals[comp.code] || {};
            let totalContent = '';

            Object.keys(totals).forEach(field => {
                const value = totals[field] || 0;
                if (field === 'PRD_QT_P' || field === 'PAP_QT_P') {
                    totalContent += value.toLocaleString('pt-BR');
                } else if (field === 'PRD_VL_P' || field === 'PAP_VALOR') {
                    totalContent += 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                } else {
                    totalContent += value.toLocaleString('pt-BR');
                }
                totalContent += '<br>';
            });

            matrixHtml += `<td class="px-2 py-2 border text-xs text-center">${totalContent || '-'}</td>`;
        });

        // Grand total
        let grandTotalContent = '';
        if (matrixData.grand_totals) {
            Object.keys(matrixData.grand_totals).forEach(field => {
                const value = matrixData.grand_totals[field] || 0;
                if (field === 'PRD_QT_P' || field === 'PAP_QT_P') {
                    grandTotalContent += value.toLocaleString('pt-BR');
                } else if (field === 'PRD_VL_P' || field === 'PAP_VALOR') {
                    grandTotalContent += 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                } else {
                    grandTotalContent += value.toLocaleString('pt-BR');
                }
                grandTotalContent += '<br>';
            });
        }
        matrixHtml += `<td class="px-2 py-2 border text-xs text-center font-bold bg-blue-200">${grandTotalContent || '-'}</td>`;
        matrixHtml += `</tr></tbody></table></div>`;

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
            renderMatrixResults(data.data, container);
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
     * Show error message
     */
    function showError(message, containerId = 'results-container') {
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

