import axios from 'axios';
import type { OrderListItem, OrderPayload, OrderResponse, TemplateResponse } from '../types/api';

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    Accept: 'application/json',
  },
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

  public static async submitOrder(payload: OrderPayload): Promise<OrderResponse> {
    const response = await client.post<OrderResponse>('/orders', payload);
    return response.data;
  }

  public static async getOrders(): Promise<{ orders: OrderListItem[] }> {
    const response = await client.get<{ orders: OrderListItem[] }>('/orders');
    return response.data;
  }
}
