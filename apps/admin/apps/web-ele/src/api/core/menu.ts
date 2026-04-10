import type { RouteRecordStringComponent } from '@vben/types';

import { requestClient } from '#/api/request';

/** Starship 菜单节点（与 AdminPermissionService::menuTreeForAdmin 一致） */
interface StarshipMenuNode {
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
  children: StarshipMenuNode[];
}

const DASHBOARD_VIEW = 'views/dashboard/workspace/index.vue';
const PLACEHOLDER_VIEW = 'views/_core/fallback/coming-soon.vue';

const ICON_MAP: Record<string, string> = {
  HomeFilled: 'ep:home-filled',
  Setting: 'ep:setting',
  User: 'ep:user',
  UserFilled: 'ep:user-filled',
  Menu: 'ep:menu',
  OfficeBuilding: 'ep:office-building',
  Postcard: 'ep:postcard',
  Collection: 'ep:collection',
  Tools: 'ep:setting',
  Paperclip: 'ep:paperclip',
};

function mapIcon(icon: string | null | undefined): string {
  if (!icon) {
    return 'lucide:folder';
  }
  return ICON_MAP[icon] ?? 'lucide:file-text';
}

function resolvePageComponent(path: string | null): string {
  if (path === '/dashboard') {
    return DASHBOARD_VIEW;
  }
  return PLACEHOLDER_VIEW;
}

function transformMenus(nodes: StarshipMenuNode[]): RouteRecordStringComponent[] {
  const result: RouteRecordStringComponent[] = [];

  for (const node of nodes) {
    if (node.type === 3) {
      continue;
    }

    const name = `SaMenu${node.id}`;
    const baseMeta = {
      title: node.name,
      icon: mapIcon(node.icon),
      order: node.sort,
    };

    if (node.type === 1) {
      const children = transformMenus(node.children ?? []);
      result.push({
        name,
        path: node.path ?? `/g-${node.id}`,
        meta: baseMeta,
        children,
      } as RouteRecordStringComponent);
      continue;
    }

    const authority = node.permission ? [node.permission] : [];

    result.push({
      name,
      path: node.path ?? '/',
      component: resolvePageComponent(node.path),
      meta: {
        ...baseMeta,
        authority,
        keepAlive: node.keep_alive === 1,
      },
    } as RouteRecordStringComponent);
  }

  return result;
}

/**
 * 后端菜单驱动路由（Starship：`GET /admin-api/auth/menus`）
 */
export async function getAllMenusApi() {
  const tree = await requestClient.get<StarshipMenuNode[]>('/auth/menus');
  return transformMenus(tree);
}
