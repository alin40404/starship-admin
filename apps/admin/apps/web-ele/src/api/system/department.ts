import { requestClient } from '#/api/request';

export interface DeptNode {
  id: number;
  parent_id: number;
  name: string;
  leader: string | null;
  phone: string | null;
  email: string | null;
  sort: number;
  status: number;
  remark: string | null;
  children?: DeptNode[];
}

export function fetchDepartmentTree() {
  return requestClient.get<DeptNode[]>('/system/departments/tree');
}
