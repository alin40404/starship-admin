import type { LaravelPaginate } from '#/features/system/api/types';

import { requestClient } from '#/api/request';

export interface ProductRow {
  id: number;
  name: string;
  price: number;
  stock: number;
  cover: string | null;
  description: string | null;
  status: number;
  created_at?: string;
  updated_at?: string;
}

export interface OrderRow {
  id: number;
  order_no: string;
  member_id: number | null;
  product_id: number;
  product_name: string;
  amount: number;
  quantity: number;
  status: number;
  pay_channel: string | null;
  paid_at: string | null;
  remark: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface ProductSavePayload {
  name: string;
  price: number;
  stock: number;
  cover?: string | null;
  description?: string | null;
  status?: number;
}

export interface OrderCreatePayload {
  product_id: number;
  quantity?: number;
  member_id?: number | null;
  remark?: string | null;
}

export interface PrepayResult {
  order: OrderRow;
  payment: Record<string, unknown>;
}

export function fetchProductPage(params: Record<string, unknown>) {
  return requestClient.get<LaravelPaginate<ProductRow>>('/shop/products', {
    params,
  });
}

export function fetchProduct(id: number) {
  return requestClient.get<ProductRow>(`/shop/products/${id}`);
}

export function createProduct(data: ProductSavePayload) {
  return requestClient.post<ProductRow>('/shop/products', data);
}

export function updateProduct(id: number, data: Partial<ProductSavePayload>) {
  return requestClient.put<ProductRow>(`/shop/products/${id}`, data);
}

export function updateProductStatus(id: number, status: number) {
  return requestClient.put<ProductRow>(`/shop/products/${id}/status`, {
    status,
  });
}

export function deleteProduct(id: number) {
  return requestClient.delete(`/shop/products/${id}`);
}

export function fetchOrderPage(params: Record<string, unknown>) {
  return requestClient.get<LaravelPaginate<OrderRow>>('/shop/orders', {
    params,
  });
}

export function createOrder(data: OrderCreatePayload) {
  return requestClient.post<OrderRow>('/shop/orders', data);
}

export function prepayOrder(id: number) {
  return requestClient.post<PrepayResult>(`/shop/orders/${id}/prepay`);
}

export function syncOrderStatus(id: number) {
  return requestClient.post<OrderRow>(`/shop/orders/${id}/sync-status`);
}

export function cancelOrder(id: number) {
  return requestClient.post<OrderRow>(`/shop/orders/${id}/cancel`);
}

export function deleteOrder(id: number) {
  return requestClient.delete(`/shop/orders/${id}`);
}
