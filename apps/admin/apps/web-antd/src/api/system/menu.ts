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
  /** 侧栏高亮：须为已有菜单的 path，对应路由 meta.activePath */
  active_path: string | null;
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
  /** 由后端从 other_settings 展开，供表格/路由 */
  visible: number;
  keep_alive: number;
  affix_tab: number;
  status: number;
  remark: string | null;
  hide_submenu: number;
  hide_in_breadcrumb: number;
  hide_in_tab: number;
  children: MenuTreeNode[];
}

/** 与 menus.other_settings JSON 一致（均为 0|1） */
export interface MenuOtherSettingsPayload {
  hide_menu: number;
  hide_submenu: number;
  hide_in_breadcrumb: number;
  hide_in_tab: number;
  affix_tab: number;
  keep_alive: number;
}

/** 创建/更新 body（与 MenuController 校验一致） */
export interface MenuSavePayload {
  parent_id?: number;
  type: number;
  name: string;
  title?: string | null;
  path?: string | null;
  active_path?: string | null;
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
  status?: number;
  remark?: string | null;
  other_settings: MenuOtherSettingsPayload;
}

export function fetchMenuTree() {
  return requestClient.get<MenuTreeNode[]>('/system/menus/tree');
}

/** @returns true 表示与其它菜单的路由地址重复（编辑时传 excludeId 排除当前菜单） */
export async function isMenuPathDuplicate(
  path: string,
  excludeId?: number,
): Promise<boolean> {
  const trimmed = path.trim();
  if (!trimmed) {
    return false;
  }
  const data = await requestClient.get<{ exists: boolean }>(
    '/system/menus/path-exists',
    {
      params: {
        path: trimmed,
        ...(excludeId !== undefined && excludeId > 0
          ? { exclude_id: excludeId }
          : {}),
      },
    },
  );
  return Boolean(data?.exists);
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
