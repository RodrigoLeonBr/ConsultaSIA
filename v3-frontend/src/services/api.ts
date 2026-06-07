import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:3000',
    timeout: 30000,
});

export const apiEndpoints = {
    // Dispara uma rotina pesada (ex: matching, consolidação)
    createReportJob: async (jobData: { type: string, parameters: any }) => {
        return api.post('/reports/jobs', jobData);
    },

    // Polling de status do job
    getJobStatus: async (jobId: number | string) => {
        return api.get(`/reports/jobs/${jobId}`);
    },

    // Paginação dos resultados já consolidados (persistidos pelo worker)
    // Enviar config com signal para suportar AbortController
    getReportResults: async (resultId: number | string, page: number, limit: number, config?: any) => {
        return api.get(`/reports/jobs/${resultId}/results`, {
            params: { page, limit },
            ...config
        });
    },

    // Consulta Síncrona SIA (s_prd)
    getSiaReports: async (params: { page: number; limit: number; competence?: string; providerId?: string }, config?: any) => {
        return api.get('/reports/sia', {
            params,
            ...config
        });
    },

    // Faturamento por Prestador — paginação server-side com GROUP BY hierárquico
    getSiaBillingProvider: async (
        params: { page: number; limit: number; competence: string; providerId?: string },
        config?: any,
    ) => {
        return api.get('/reports/sia/faturamento-prestador', {
            params,
            ...config,
        });
    },

    // Catálogo de campos SIA (metadados para SiaDynamicPage)
    getSiaDynamicMetadata: async () => {
        return api.get('/reports/sia/metadata');
    },

    // Relatório dinâmico de Produção SIA (POST com select/filters/sort/paginação)
    getSiaDynamicProduction: async (
        body: {
            competence: string;
            select: string[];
            filters?: Array<{ fieldId: string; operator: string; value: string | string[] }>;
            page?: number;
            pageSize?: number;
            sort?: { fieldId: string; direction: 'ASC' | 'DESC' };
        },
        config?: any,
    ) => {
        return api.post('/reports/sia/production', body, config);
    },

    // Download de arquivo gerado por job de exportação
    getExportDownloadUrl: (jobId: number | string): string => {
        const base = import.meta.env.VITE_API_URL || 'http://localhost:3000';
        return `${base}/reports/jobs/${jobId}/download`;
    },
};

export default api;
