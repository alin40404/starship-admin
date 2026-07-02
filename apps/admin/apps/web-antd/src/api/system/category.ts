import type { LaravelPaginate } from './types';

import { requestClient } from '#/api/request';

export interface CategoryRow {
  id: number;
  dict_type_id: number;
  label: string;
  value: string;
  sort: number;
  status: number;
  remark: string | null;
  created_at: string;
  updated_at: string;
}

export interface CategoryPageQuery {
  page: number;
  per_page: number;
  label?: string;
  value?: string;
  status?: number;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}

export interface CategorySavePayload {
  label: string;
  value: string;
  sort?: number;
  status?: number;
  remark?: string | null;
}

export function fetchCategoryPage(params: CategoryPageQuery) {
  return requestClient.get<LaravelPaginate<CategoryRow>>('/system/categories', {
    params,
  });
}

export function createCategory(data: CategorySavePayload) {
  return requestClient.post<CategoryRow>('/system/categories', data);
}

export function updateCategory(id: number, data: Partial<CategorySavePayload>) {
  return requestClient.put<CategoryRow>(`/system/categories/${id}`, data);
}

export function deleteCategory(id: number) {
  return requestClient.delete(`/system/categories/${id}`);
}
