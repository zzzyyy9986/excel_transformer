import axios from 'axios';
import type { TemplateResponse } from '../types/api';
import { getClientId } from '../utils/clientId';

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    Accept: 'application/json',
  },
});

client.interceptors.request.use((config) => {
  config.headers.set('X-Client-Id', getClientId());
  return config;
});

export class QueryService {
  public static async getTemplate(): Promise<TemplateResponse> {
    const response = await client.get<TemplateResponse>('/template');
    return response.data;
  }

  public static async uploadTemplate(file: File): Promise<TemplateResponse> {
    const formData = new FormData();
    formData.append('file', file);

    const response = await client.post<TemplateResponse>('/template/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    return response.data;
  }
}
