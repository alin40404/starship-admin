import type { LaravelPaginate } from './types';

import { requestClient } from '#/api/request';

export interface RoleRow {
  id: number;
  name: string;
  code: string;
  data_scope: number;
  sort: number;
  status: number;
  remark: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface RoleDetail extends RoleRow {
  menu_ids: number[];
  dept_ids: number[];
}

export interface RolePageQuery {
  page: number;
  per_page: number;
  name?: string;
  code?: string;
  status?: number;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}

export function fetchRolePage(params: RolePageQuery) {
  return requestClient.get<LaravelPaginate<RoleRow>>('/system/roles', {
    params,
  });
}

export function fetchRole(id: number) {
  return requestClient.get<RoleDetail>(`/system/roles/${id}`);
}

export interface RoleSavePayload {
  name: string;
  code: string;
  data_scope?: number;
  sort?: number;
  status?: number;
  remark?: string;
}

export function createRole(data: RoleSavePayload) {
  return requestClient.post<RoleRow>('/system/roles', data);
}

export function updateRole(id: number, data: Partial<RoleSavePayload>) {
  return requestClient.put<RoleRow>(`/system/roles/${id}`, data);
}

export function deleteRole(id: number) {
  return requestClient.delete(`/system/roles/${id}`);
}

export function batchDeleteRoles(ids: number[]) {
  return requestClient.post<{ deleted: number }>(
    '/system/roles/batch-destroy',
    { ids },
  );
}

export function batchUpdateRoleStatus(ids: number[], status: 0 | 1) {
  return requestClient.put<{ updated: number }>('/system/roles/batch-status', {
    ids,
    status,
  });
}

export function syncRoleMenus(id: number, menuIds: number[]) {
  return requestClient.put<{ menu_ids: number[] }>(
    `/system/roles/${id}/menus`,
    { menu_ids: menuIds },
  );
}

export function syncRoleDepts(id: number, deptIds: number[]) {
  return requestClient.put<{ dept_ids: number[] }>(
    `/system/roles/${id}/depts`,
    { dept_ids: deptIds },
  );
}
