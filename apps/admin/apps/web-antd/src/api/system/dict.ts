import type { LaravelPaginate } from './types';

import { requestClient } from '#/api/request';

export interface DictTypeRow {
  id: number;
  name: string;
  code: string;
  status: number;
  remark: string | null;
  items_count?: number;
  created_at?: string;
  updated_at?: string;
}

export interface DictItemRow {
  id: number;
  dict_type_id: number;
  label: string;
  value: string;
  color: string | null;
  sort: number;
  status: number;
  remark: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface DictTypePageQuery {
  page: number;
  per_page: number;
  name?: string;
  code?: string;
  status?: number;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}

export interface DictTypeSavePayload {
  name: string;
  code: string;
  status?: number;
  remark?: string;
}

export interface DictItemPageQuery {
  page: number;
  per_page: number;
  dict_type_id: number;
}

export interface DictItemSavePayload {
  dict_type_id: number;
  label: string;
  value: string;
  color?: string;
  sort?: number;
  status?: number;
  remark?: string;
}

export function fetchDictTypePage(params: DictTypePageQuery) {
  return requestClient.get<LaravelPaginate<DictTypeRow>>('/system/dict-types', {
    params,
  });
}

export function createDictType(data: DictTypeSavePayload) {
  return requestClient.post<DictTypeRow>('/system/dict-types', data);
}

export function updateDictType(id: number, data: Partial<DictTypeSavePayload>) {
  return requestClient.put<DictTypeRow>(`/system/dict-types/${id}`, data);
}

export function deleteDictType(id: number) {
  return requestClient.delete(`/system/dict-types/${id}`);
}

export function fetchDictItemPage(params: DictItemPageQuery) {
  return requestClient.get<LaravelPaginate<DictItemRow>>('/system/dict-items', {
    params,
  });
}

export function createDictItem(data: DictItemSavePayload) {
  return requestClient.post<DictItemRow>('/system/dict-items', data);
}

export function updateDictItem(id: number, data: Partial<DictItemSavePayload>) {
  return requestClient.put<DictItemRow>(`/system/dict-items/${id}`, data);
}

export function deleteDictItem(id: number) {
  return requestClient.delete(`/system/dict-items/${id}`);
}
