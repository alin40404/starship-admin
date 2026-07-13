import type { LaravelPaginate } from '#/api/system/types';

import { requestClient } from '#/api/request';

export interface ProductsRow {
  name: string;
  code: string;
  secret: string;
  price: number;
  stock: number;
  sort: number;
  product_type: string;
  flags: string;
  is_featured: number;
  score: number;
  category_id: number;
  parent_id: number;
  cover: string;
  attachment: string;
  brochure: string;
  summary: string;
  content: string;
  icon: string;
  keywords: string;
  tag: string;
  color: string;
  published_at: string;
  sale_date: string;
  sale_time: string;
  remark: string;
  status: string;
  created_at?: string;
  updated_at?: string;
}

export interface ProductsPageQuery {
  page: number;
  per_page: number;
  keyword?: string;

  name?: string;
  code?: string;
  tag?: string;
  status?: string;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}

export interface ProductsSavePayload {
  name: string;
  code?: string;
  secret?: string;
  price: number;
  stock: number;
  sort: number;
  product_type: string;
  flags?: string;
  is_featured: number;
  score: number;
  category_id?: number;
  parent_id: number;
  cover?: string;
  attachment?: string;
  brochure?: string;
  summary?: string;
  content?: string;
  icon?: string;
  keywords?: string;
  tag?: string;
  color?: string;
  published_at?: string;
  sale_date?: string;
  sale_time?: string;
  remark?: string;
  status: string;
}

export function fetchProductsPage(params: ProductsPageQuery) {
  return requestClient.get<LaravelPaginate<ProductsRow>>('/generator/products', {
    params,
  });
}

export function createProducts(data: ProductsSavePayload) {
  return requestClient.post<ProductsRow>('/generator/products', data);
}

export function updateProducts(id: number, data: Partial<ProductsSavePayload>) {
  return requestClient.put<ProductsRow>(`/generator/products/${id}`, data);
}

export function deleteProducts(id: number) {
  return requestClient.delete(`/generator/products/${id}`);
}
export function batchDeleteProducts(ids: number[]) {
  return requestClient.post<{ deleted: number }>(
    '/generator/products/batch-destroy',
    { ids },
  );
}

export function batchUpdateProductsStatus(ids: number[], status: 'normal' | 'hidden') {
  return requestClient.put<{ updated: number }>('/generator/products/batch-status', {
    ids,
    status,
  });
}
