import type { LaravelPaginate } from './types';

import { requestClient } from '#/api/request';

export interface AdminRow {
  id: number;
  username: string;
  nickname: string | null;
  real_name: string | null;
  avatar: string | null;
  email: string | null;
  mobile: string | null;
  dept_id: number | null;
  status: number;
  is_super: number;
  remark: string | null;
  created_at: string;
  updated_at: string;
  department?: { id: number; name: string } | null;
  roles?: { id: number; name: string }[];
  posts?: { id: number; name: string }[];
}

export interface AdminSavePayload {
  username?: string;
  password?: string;
  nickname?: string;
  real_name?: string;
  avatar?: string;
  email?: string;
  mobile?: string;
  dept_id?: number;
  status?: number;
  is_super?: number;
  remark?: string;
  role_ids: number[];
  post_ids?: number[];
}

export interface AdminPageQuery {
  page: number;
  per_page: number;
  keyword?: string;
  username?: string;
  name?: string;
  email?: string;
  mobile?: string;
  dept_id?: number;
  dept_name?: string;
  updated_start?: string;
  updated_end?: string;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}

export function fetchAdminPage(params: AdminPageQuery) {
  return requestClient.get<LaravelPaginate<AdminRow>>('/system/admins', {
    params,
  });
}

export function createAdmin(data: AdminSavePayload) {
  return requestClient.post<AdminRow>('/system/admins', data);
}

export function updateAdmin(id: number, data: Partial<AdminSavePayload>) {
  return requestClient.put<AdminRow>(`/system/admins/${id}`, data);
}

export function updateAdminStatus(id: number, status: 0 | 1) {
  return requestClient.put<AdminRow>(`/system/admins/${id}/status`, {
    status,
  });
}

export function deleteAdmin(id: number) {
  return requestClient.delete(`/system/admins/${id}`);
}

export function batchDeleteAdmins(ids: number[]) {
  return requestClient.post<{ deleted: number }>(
    '/system/admins/batch-destroy',
    { ids },
  );
}

export function batchUpdateAdminStatus(ids: number[], status: 0 | 1) {
  return requestClient.put<{ updated: number }>('/system/admins/batch-status', {
    ids,
    status,
  });
}
