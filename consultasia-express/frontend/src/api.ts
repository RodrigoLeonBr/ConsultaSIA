import axios from 'axios';
import type { DynamicResult, Job, JobResults, MetadataResponse } from './types';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:3001',
  headers: { 'Content-Type': 'application/json' },
});

export const getMetadata = (): Promise<MetadataResponse> =>
  api.get('/reports/sia/metadata').then(r => r.data);

export const getSimpleList = (competence: string, page: number, pageSize: number) =>
  api.get('/reports/sia', { params: { competence, page, pageSize } }).then(r => r.data);

export const postProduction = (body: object, signal?: AbortSignal): Promise<DynamicResult> =>
  api.post('/reports/sia/production', body, { signal }).then(r => r.data);

export const createJob = (type: string, parameters: object): Promise<Job> =>
  api.post('/reports/jobs', { type, parameters }).then(r => r.data);

export const getJob = (id: number): Promise<Job> =>
  api.get(`/reports/jobs/${id}`).then(r => r.data);

export const getJobResults = (id: number, page: number, limit: number): Promise<JobResults> =>
  api.get(`/reports/jobs/${id}/results`, { params: { page, limit } }).then(r => r.data);
