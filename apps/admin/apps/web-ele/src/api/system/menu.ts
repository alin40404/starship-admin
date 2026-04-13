import type { LaravelPage } from './_types';

import { pageToItems } from './_types';

import { requestClient } from '#/api/request';

export interface MenuNode {
  id: number;
  parent_id: number;
  type: number;
  name: string;
  path: string | null;
  component: string | null;
  permission: string | null;
  icon: string | null;
  sort: number;
  visible: number;
  keep_alive: number;
  status: number;
  remark: string | null;
  children?: MenuNode[];
}

export interface MenuSavePayload {
  parent_id?: number;
  type?: number;
  name?: string;
  path?: string | null;
  component?: string | null;
  permission?: string | null;
  icon?: string | null;
  sort?: number;
  visible?: number;
  keep_alive?: number;
  status?: number;
  remark?: string | null;
}

export function fetchMenuTree() {
  return requestClient.get<MenuNode[]>('/system/menus/tree');
}

export async function fetchMenuPage(params: {
  page?: number;
  per_page?: number;
  type?: number;
}) {
  const page = await requestClient.get<LaravelPage<MenuNode>>('/system/menus', {
    params,
  });
  return pageToItems(page);
}

export function createMenu(data: MenuSavePayload) {
  return requestClient.post<MenuNode>('/system/menus', data);
}

export function updateMenu(id: number, data: MenuSavePayload) {
  return requestClient.put<MenuNode>(`/system/menus/${id}`, data);
}

export function deleteMenu(id: number) {
  return requestClient.delete(`/system/menus/${id}`);
}
