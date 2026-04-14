import { requestClient } from '#/api/request';

export interface DeptNode {
  id: number;
  parent_id: number;
  name: string;
  children?: DeptNode[];
}

export function fetchDepartmentTree() {
  return requestClient.get<DeptNode[]>('/system/departments/tree');
}
