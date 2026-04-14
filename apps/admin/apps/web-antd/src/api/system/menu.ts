import { requestClient } from '#/api/request';

/** 与 MenuController::tree 一致 */
export interface MenuTreeNode {
  id: number;
  parent_id: number;
  /** 1目录 2菜单 3按钮 4内嵌 5外链 */
  type: number;
  name: string;
  title: string | null;
  path: string | null;
  component: string | null;
  permission: string | null;
  icon: string | null;
  active_icon: string | null;
  badge_type: string | null;
  badge: string | null;
  badge_variants: string | null;
  link_url: string | null;
  iframe_src: string | null;
  sort: number;
  visible: number;
  keep_alive: number;
  status: number;
  remark: string | null;
  hide_submenu: number;
  hide_in_breadcrumb: number;
  hide_in_tab: number;
  children: MenuTreeNode[];
}

/** 创建/更新 body（与 MenuController 校验一致） */
export interface MenuSavePayload {
  parent_id?: number;
  type: number;
  name: string;
  title?: string | null;
  path?: string | null;
  component?: string | null;
  permission?: string | null;
  icon?: string | null;
  active_icon?: string | null;
  badge_type?: string | null;
  badge?: string | null;
  badge_variants?: string | null;
  link_url?: string | null;
  iframe_src?: string | null;
  sort?: number;
  visible?: number;
  keep_alive?: number;
  status?: number;
  remark?: string | null;
  hide_submenu?: number;
  hide_in_breadcrumb?: number;
  hide_in_tab?: number;
}

export function fetchMenuTree() {
  return requestClient.get<MenuTreeNode[]>('/system/menus/tree');
}

export function createMenu(data: MenuSavePayload) {
  return requestClient.post<MenuTreeNode>('/system/menus', data);
}

export function updateMenu(id: number, data: Partial<MenuSavePayload>) {
  return requestClient.put<MenuTreeNode>(`/system/menus/${id}`, data);
}

export function deleteMenu(id: number) {
  return requestClient.delete(`/system/menus/${id}`);
}
