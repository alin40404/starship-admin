import type { LaravelPaginate } from './types';

import { requestClient } from '#/api/request';

export interface UserGroupRow {
  id: number;
  name: string;
  code: string;
  sort: number;
  status: number;
  remark: string | null;
  admins_count?: number;
  created_at?: string;
  updated_at?: string;
}

export interface UserGroupDetail extends UserGroupRow {
  admin_ids: number[];
}

export interface UserGroupPageQuery {
  page: number;
  per_page: number;
  keyword?: string;
  name?: string;
  code?: string;
  status?: number;
  sort_field?: string;
  sort_order?: 'asc' | 'desc';
}

export interface UserGroupSavePayload {
  name: string;
  code: string;
  sort?: number;
  status?: number;
  remark?: string;
}

export function fetchUserGroupPage(params: UserGroupPageQuery) {
  return requestClient.get<LaravelPaginate<UserGroupRow>>(
    '/system/user-groups',
    { params },
  );
}

export function fetchUserGroup(id: number) {
  return requestClient.get<UserGroupDetail>(`/system/user-groups/${id}`);
}

export function createUserGroup(data: UserGroupSavePayload) {
  return requestClient.post<UserGroupRow>('/system/user-groups', data);
}

export function updateUserGroup(id: number, data: Partial<UserGroupSavePayload>) {
  return requestClient.post<UserGroupRow>(`/system/user-groups/${id}`, data);
}

export function deleteUserGroup(id: number) {
  return requestClient.post(`/system/user-groups/${id}/delete`);
}

export function batchDeleteUserGroups(ids: number[]) {
  return requestClient.post<{ deleted: number }>(
    '/system/user-groups/batch-destroy',
    { ids },
  );
}

export function syncUserGroupAdmins(id: number, adminIds: number[]) {
  return requestClient.post<{ admin_ids: number[] }>(
    `/system/user-groups/${id}/admins`,
    { admin_ids: adminIds },
  );
}
