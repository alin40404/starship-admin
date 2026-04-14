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
const DASHBOARD_ANALYTICS_VIEW = 'views/dashboard/analytics/index.vue';
const PLACEHOLDER_VIEW = 'views/_core/fallback/coming-soon.vue';

/** 权限中心：后端菜单 path → 页面组件 */
const PERMISSION_PAGE_BY_PATH: Record<string, string> = {
  '/permission/admins': 'views/permission/admin/index.vue',
  '/permission/roles': 'views/permission/role/index.vue',
  '/permission/menus': 'views/permission/menu/index.vue',
  '/permission/permission-points': 'views/permission/permission-point/index.vue',
  '/permission/data-permission': 'views/permission/data-scope/index.vue',
};

function pascalToKebabCase(s: string): string {
  return s.replace(/([A-Z])/g, '-$1').toLowerCase().replace(/^-/, '');
}

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
  Lock: 'ep:lock',
  Key: 'ep:key',
  Share: 'ep:share',
  Avatar: 'ep:avatar',
  TrendCharts: 'ep:trend-charts',
  Operation: 'ep:operation',
  Location: 'ep:location',
  Bell: 'ep:bell',
  FolderOpened: 'ep:folder-opened',
  Files: 'ep:files',
  Coin: 'ep:coin',
  Monitor: 'ep:monitor',
  Document: 'ep:document',
  Notebook: 'ep:notebook',
  WarningFilled: 'ep:warning-filled',
  View: 'ep:view',
  Timer: 'ep:timer',
  Cpu: 'ep:cpu',
  MagicStick: 'ep:magic-stick',
  Grid: 'ep:grid',
  Link: 'ep:link',
  Odometer: 'ep:odometer',
  Histogram: 'ep:histogram',
  List: 'ep:list',
  Promotion: 'ep:promotion',
  StarFilled: 'ep:star-filled',
};

/**
 * 将 `sa_menus.icon`（Element 组件名或已是 `collection:name`）转为 Iconify 名，供 `IconifyIcon`、权限树等使用。
 */
export function menuIconToIconify(icon: string | null | undefined): string | undefined {
  if (!icon?.trim()) {
    return undefined;
  }
  const t = icon.trim();
  if (t.includes(':')) {
    return t;
  }
  return ICON_MAP[t] ?? `ep:${pascalToKebabCase(t)}`;
}

function mapIcon(icon: string | null | undefined): string {
  if (!icon) {
    return 'lucide:folder';
  }
  return menuIconToIconify(icon) ?? 'lucide:file-text';
}

function resolvePageComponent(path: string | null): string {
  if (path === '/dashboard' || path === '/dashboard/console') {
    return DASHBOARD_VIEW;
  }
  if (path === '/dashboard/overview') {
    return DASHBOARD_ANALYTICS_VIEW;
  }
  if (path && PERMISSION_PAGE_BY_PATH[path]) {
    return PERMISSION_PAGE_BY_PATH[path]!;
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
