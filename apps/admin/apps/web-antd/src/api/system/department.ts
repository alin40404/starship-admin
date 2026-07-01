import { requestClient } from '#/api/request';

export interface DeptNode {
  id: number;
  parent_id: number;
  name: string;
  children?: DeptNode[];
}

export function fetchDepartmentTree(unscoped = false) {
  return requestClient.get<DeptNode[]>('/system/departments/tree', {
    params: unscoped ? { unscoped: 1 } : undefined,
  });
}
