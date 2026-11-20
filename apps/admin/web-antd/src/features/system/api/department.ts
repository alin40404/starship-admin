import type { LaravelPaginate } from './types';

import { requestClient } from '#/api/request';

export interface DeptNode {
  id: number;
  parent_id: number;
  name: string;
  children?: DeptNode[];
}

export interface DepartmentRow {
  id: number;
  parent_id: number;
  name: string;
  leader_id: number | null;
  leader?: {
    id: number;
    nickname: string | null;
    real_name: string | null;
    username: string;
  } | null;
  leader_name?: string | null;
  phone: string | null;
  email: string | null;
  sort: number;
  status: number;
  remark: string | null;
  created_at?: string;
  updated_at?: string;
  children?: DepartmentTreeNode[];
}

export type DepartmentTreeNode = DepartmentRow;

export interface DepartmentSavePayload {
  parent_id?: number;
  name: string;
  leader_id?: number | null;
  phone?: string;
  email?: string;
  sort?: number;
  status?: number;
  remark?: string;
}

export interface DepartmentOptionsTreeNode {
  id: number;
  parent_id: number;
  name: string;
  children?: DepartmentOptionsTreeNode[];
}

export function fetchDepartmentTree(unscoped = false) {
  return requestClient.get<DepartmentTreeNode[]>('/system/departments/tree', {
    params: unscoped ? { unscoped: 1 } : undefined,
  });
}

/** 部门选择器（需登录，无按钮权限） */
export function fetchDepartmentOptionsTree(unscoped = false) {
  return requestClient.get<DepartmentOptionsTreeNode[]>(
    '/system/department-options/tree',
    {
      params: unscoped ? { unscoped: 1 } : undefined,
    },
  );
}

export function fetchDepartment(id: number) {
  return requestClient.get<DepartmentRow>(`/system/departments/${id}`);
}

export function createDepartment(data: DepartmentSavePayload) {
  return requestClient.post<DepartmentRow>('/system/departments', data);
}

export function updateDepartment(
  id: number,
  data: Partial<DepartmentSavePayload>,
) {
  return requestClient.post<DepartmentRow>(`/system/departments/${id}`, data);
}

export function updateDepartmentStatus(id: number, status: 0 | 1) {
  return requestClient.post<DepartmentRow>(`/system/departments/${id}/status`, {
    status,
  });
}

export function updateDepartmentSort(id: number, sort: number) {
  return requestClient.post<DepartmentRow>(`/system/departments/${id}/sort`, {
    sort,
  });
}

export function deleteDepartment(id: number) {
  return requestClient.post(`/system/departments/${id}/delete`);
}

export function batchDeleteDepartments(ids: number[]) {
  return requestClient.post<{ deleted: number; skipped: number }>(
    '/system/departments/batch-destroy',
    { ids },
  );
}

export function batchUpdateDepartmentStatus(ids: number[], status: 0 | 1) {
  return requestClient.post<{ updated: number }>(
    '/system/departments/batch-status',
    { ids, status },
  );
}

export function mapDeptForTreeSelect(
  nodes: DeptNode[] | DepartmentTreeNode[] | DepartmentOptionsTreeNode[],
): { label: string; value: number; children?: ReturnType<typeof mapDeptForTreeSelect> }[] {
  return nodes.map((n) => ({
    label: n.name,
    value: n.id,
    children: n.children?.length
      ? mapDeptForTreeSelect(n.children)
      : undefined,
  }));
}

export function fetchDepartmentPage(params: {
  page: number;
  per_page: number;
}) {
  return requestClient.get<LaravelPaginate<DepartmentRow>>(
    '/system/departments',
    { params },
  );
}
