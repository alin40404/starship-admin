import { requestClient } from '#/api/request';

export interface AreaTreeNode {
  id: number;
  pid: number;
  shortname: string | null;
  name: string;
  mergename: string | null;
  level: number | null;
  pinyin: string | null;
  code: string | null;
  zip: string | null;
  first: string | null;
  lng: string | null;
  lat: string | null;
  children?: AreaTreeNode[];
}

export interface AreaSavePayload {
  pid?: number;
  shortname?: string;
  name: string;
  mergename?: string;
  level?: number;
  pinyin?: string;
  code?: string;
  zip?: string;
  first?: string;
  lng?: string;
  lat?: string;
}

export interface AreaTreeQuery {
  name?: string;
  level?: number;
  pinyin?: string;
}

export function fetchAreaTree(params?: AreaTreeQuery) {
  return requestClient.get<AreaTreeNode[]>('/system/areas/tree', { params });
}

export function fetchAreaChildren(pid = 0) {
  return requestClient.get<AreaTreeNode[]>('/system/areas/children', {
    params: { pid },
  });
}

export function fetchArea(id: number) {
  return requestClient.get<AreaTreeNode>(`/system/areas/${id}`);
}

export function createArea(data: AreaSavePayload) {
  return requestClient.post<AreaTreeNode>('/system/areas', data);
}

export function updateArea(id: number, data: Partial<AreaSavePayload>) {
  return requestClient.put<AreaTreeNode>(`/system/areas/${id}`, data);
}

export function deleteArea(id: number) {
  return requestClient.delete(`/system/areas/${id}`);
}

export function mapAreaForTreeSelect(
  nodes: AreaTreeNode[],
): { label: string; value: number; children?: ReturnType<typeof mapAreaForTreeSelect> }[] {
  return nodes.map((node) => ({
    label: node.name,
    value: node.id,
    children: node.children?.length
      ? mapAreaForTreeSelect(node.children)
      : undefined,
  }));
}
