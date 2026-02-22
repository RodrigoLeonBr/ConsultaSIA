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

    // Paginação dos resultados já consolidados
    // Enviar cancelToken config caso precise cancelar requests pendentes
    getReportResults: async (resultId: number | string, page: number, limit: number, config?: any) => {
        return api.get(`/reports/results/${resultId}`, {
            params: { page, limit },
            ...config
        });
    },

    // Consulta Síncrona SIA (S_PAP)
    getSiaReports: async (params: { page: number; limit: number; competence?: string; providerId?: string }, config?: any) => {
        return api.get('/reports/sia', {
            params,
            ...config
        });
    }
};

export default api;
