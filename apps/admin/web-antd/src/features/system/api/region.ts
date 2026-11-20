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
  has_child?: boolean;
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

export interface AreaPinyinPreview {
  first: string | null;
  pinyin: string | null;
}

export interface AreaCascaderOption {
  children?: AreaCascaderOption[];
  isLeaf?: boolean;
  label: string;
  loading?: boolean;
  value: number;
}

export interface AreaCascaderInitResult {
  options: AreaCascaderOption[];
  path: number[];
}

export function fetchAreaTree(params?: AreaTreeQuery) {
  return requestClient.get<AreaTreeNode[]>('/system/areas/tree', { params });
}

/** 地区选择器（需登录，无按钮权限） */
export function fetchAreaOptionsTree(params?: AreaTreeQuery) {
  return requestClient.get<AreaTreeNode[]>('/system/area-options/tree', {
    params,
  });
}

export function fetchAreaChildren(pid = 0) {
  return requestClient.get<AreaTreeNode[]>('/system/areas/children', {
    params: { pid },
  });
}

export function fetchAreaOptionsChildren(pid = 0) {
  return requestClient.get<AreaTreeNode[]>('/system/area-options/children', {
    params: { pid },
  });
}

export function fetchAreaPinyinPreview(shortname: string) {
  return requestClient.get<AreaPinyinPreview>('/system/areas/pinyin-preview', {
    params: { shortname },
  });
}

export function fetchAreaCascaderInit(params?: {
  exclude_id?: number;
  pid?: number;
}) {
  return requestClient.get<AreaCascaderInitResult>(
    '/system/areas/cascader-init',
    { params },
  );
}

export function fetchAreaOptionsCascaderInit(params?: {
  exclude_id?: number;
  pid?: number;
}) {
  return requestClient.get<AreaCascaderInitResult>(
    '/system/area-options/cascader-init',
    { params },
  );
}

export function fetchAreaCascaderOptions(params?: {
  exclude_id?: number;
  pid?: number;
}) {
  return requestClient.get<AreaCascaderOption[]>(
    '/system/areas/cascader-options',
    { params },
  );
}

export function fetchAreaOptionsCascaderOptions(params?: {
  exclude_id?: number;
  pid?: number;
}) {
  return requestClient.get<AreaCascaderOption[]>(
    '/system/area-options/cascader-options',
    { params },
  );
}

export function fetchArea(id: number) {
  return requestClient.get<AreaTreeNode>(`/system/areas/${id}`);
}

export function createArea(data: AreaSavePayload) {
  return requestClient.post<AreaTreeNode>('/system/areas', data);
}

export function updateArea(id: number, data: Partial<AreaSavePayload>) {
  return requestClient.post<AreaTreeNode>(`/system/areas/${id}`, data);
}

export function deleteArea(id: number) {
  return requestClient.post(`/system/areas/${id}/delete`);
}

export function batchDeleteAreas(ids: number[]) {
  return requestClient.post<{ deleted: number; skipped: number }>(
    '/system/areas/batch-destroy',
    { ids },
  );
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
