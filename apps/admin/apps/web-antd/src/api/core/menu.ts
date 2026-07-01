import type { RouteRecordStringComponent } from '@vben/types';

import { requestClient } from '#/api/request';

/** Starship 菜单节点（与 AdminPermissionService::menuTreeForAdmin 一致） */
interface StarshipMenuNode {
  id: number;
  parent_id: number;
  /** 1目录 2菜单 3按钮 4内嵌 5外链 */
  type: number;
  name: string;
  title: string | null;
  path: string | null;
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
  visible: number;
  keep_alive: number;
  affix_tab: number;
  status: number;
  remark: string | null;
  hide_submenu: number;
  hide_in_breadcrumb: number;
  hide_in_tab: number;
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
  '/org/departments': 'views/org/department/index.vue',
  '/org/posts': 'views/org/post/index.vue',
  '/org/user-groups': 'views/org/user-group/index.vue',
};

/** 路由 meta 用：库内为 Iconify `collection:name`（如 ep:menu），空则用占位 */
function mapIcon(icon: string | null | undefined): string {
  const t = icon?.trim();
  if (!t) {
    return 'lucide:folder';
  }
  return t;
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

/** 数据库 `component` 优先，否则与历史逻辑一致按 path 解析 */
function resolveMenuPageComponent(node: StarshipMenuNode): string {
  const fromDb = node.component?.trim();
  if (fromDb) {
    return fromDb;
  }
  return resolvePageComponent(node.path);
}

function menuDisplayTitle(node: StarshipMenuNode): string {
  const t = node.title?.trim();
  return t ? t : node.name;
}

function buildRouteMeta(
  node: StarshipMenuNode,
  extra: Record<string, unknown> = {},
): Record<string, unknown> {
  const badgeType = node.badge_type;
  return {
    title: menuDisplayTitle(node),
    icon: mapIcon(node.icon),
    order: node.sort,
    ...(node.active_icon
      ? { activeIcon: mapIcon(node.active_icon) }
      : {}),
    ...(node.badge ? { badge: node.badge } : {}),
    ...(badgeType === 'dot' || badgeType === 'normal'
      ? { badgeType }
      : {}),
    ...(node.badge_variants
      ? { badgeVariants: node.badge_variants }
      : {}),
    hideChildrenInMenu: node.hide_submenu === 1,
    hideInBreadcrumb: node.hide_in_breadcrumb === 1,
    hideInTab: node.hide_in_tab === 1,
    ...(node.affix_tab === 1 ? { affix: true } : {}),
    ...(node.active_path?.trim()
      ? { activePath: node.active_path.trim() }
      : {}),
    ...extra,
  };
}

function transformMenus(nodes: StarshipMenuNode[]): RouteRecordStringComponent[] {
  const result: RouteRecordStringComponent[] = [];

  for (const node of nodes) {
    if (node.type === 3) {
      continue;
    }

    const name = `SaMenu${node.id}`;

    if (node.type === 1) {
      const children = transformMenus(node.children ?? []);
      result.push({
        name,
        path: node.path ?? `/g-${node.id}`,
        meta: buildRouteMeta(node),
        children,
      } as RouteRecordStringComponent);
      continue;
    }

    const authority = node.permission ? [node.permission] : [];

    if (node.type === 4) {
      result.push({
        name,
        path: node.path ?? `/iframe-${node.id}`,
        component: 'IFrameView',
        meta: buildRouteMeta(node, {
          authority,
          iframeSrc: node.iframe_src ?? '',
          keepAlive: node.keep_alive === 1,
        }),
      } as RouteRecordStringComponent);
      continue;
    }

    if (node.type === 5) {
      result.push({
        name,
        path: node.path ?? `/link-${node.id}`,
        component: PLACEHOLDER_VIEW,
        meta: buildRouteMeta(node, {
          authority,
          link: node.link_url ?? '',
          keepAlive: false,
        }),
      } as RouteRecordStringComponent);
      continue;
    }

    result.push({
      name,
      path: node.path ?? '/',
      component: resolveMenuPageComponent(node),
      meta: buildRouteMeta(node, {
        authority,
        keepAlive: node.keep_alive === 1,
      }),
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
