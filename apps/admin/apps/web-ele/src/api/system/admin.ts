import type { LaravelPage } from './_types';

import { pageToItems } from './_types';

import { requestClient } from '#/api/request';

export interface AdminRow {
  id: number;
  username: string;
  nickname: string | null;
  real_name: string | null;
  email: string | null;
  mobile: string | null;
  dept_id: number | null;
  status: number;
  is_super: number;
  remark: string | null;
  department?: { id: number; name: string } | null;
  roles?: { id: number; name: string; code: string }[];
  posts?: { id: number; name: string }[];
}

export interface AdminSavePayload {
  username?: string;
  password?: string;
  nickname?: string;
  real_name?: string;
  email?: string;
  mobile?: string;
  avatar?: string;
  dept_id?: number | null;
  status?: number;
  is_super?: number;
  remark?: string | null;
  role_ids?: number[];
  post_ids?: number[];
}

export async function fetchAdminPage(params: {
  page?: number;
  per_page?: number;
  username?: string;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}) {
  const page = await requestClient.get<LaravelPage<AdminRow>>('/system/admins', {
    params,
  });
  return pageToItems(page);
}

export function createAdmin(data: AdminSavePayload) {
  return requestClient.post<AdminRow>('/system/admins', data);
}

export function updateAdmin(id: number, data: AdminSavePayload) {
  return requestClient.put<AdminRow>(`/system/admins/${id}`, data);
}

export function deleteAdmin(id: number) {
  return requestClient.delete(`/system/admins/${id}`);
}

export function batchDeleteAdmins(ids: number[]) {
  return requestClient.post<{ deleted: number }>('/system/admins/batch-destroy', {
    ids,
  });
}
