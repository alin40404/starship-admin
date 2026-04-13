import type { LaravelPage } from './_types';

import { pageToItems } from './_types';

import { requestClient } from '#/api/request';

export interface RoleRow {
  id: number;
  name: string;
  code: string;
  data_scope: number;
  sort: number;
  status: number;
  remark: string | null;
}

export interface RoleDetail extends RoleRow {
  menu_ids: number[];
}

export interface RoleSavePayload {
  name?: string;
  code?: string;
  data_scope?: number;
  sort?: number;
  status?: number;
  remark?: string | null;
}

export async function fetchRolePage(params: { page?: number; per_page?: number }) {
  const page = await requestClient.get<LaravelPage<RoleRow>>('/system/roles', {
    params,
  });
  return pageToItems(page);
}

export function fetchRoleDetail(id: number) {
  return requestClient.get<RoleDetail>(`/system/roles/${id}`);
}

export function createRole(data: RoleSavePayload) {
  return requestClient.post<RoleRow>('/system/roles', data);
}

export function updateRole(id: number, data: RoleSavePayload) {
  return requestClient.put<RoleRow>(`/system/roles/${id}`, data);
}

export function deleteRole(id: number) {
  return requestClient.delete(`/system/roles/${id}`);
}

export function syncRoleMenus(id: number, menuIds: number[]) {
  return requestClient.put(`/system/roles/${id}/menus`, { menu_ids: menuIds });
}
