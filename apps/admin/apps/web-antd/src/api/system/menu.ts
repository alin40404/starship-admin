import { requestClient } from '#/api/request';

/** 与 MenuController::tree 一致 */
export interface MenuTreeNode {
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
  children: MenuTreeNode[];
}

export function fetchMenuTree() {
  return requestClient.get<MenuTreeNode[]>('/system/menus/tree');
}
