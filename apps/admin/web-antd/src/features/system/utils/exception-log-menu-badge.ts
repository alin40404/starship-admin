import type { MenuRecordRaw } from '@vben-core/typings';

import { useAccessStore } from '@vben/stores';

import { fetchExceptionLogUnreadCount } from '#/features/system/api/monitor';
import { MONITOR_EXCEPTION_LOG_PERMISSIONS } from '#/features/system/constants/monitor-permissions';

const EXCEPTION_LOG_MENU_PATH = '/monitor/exception-logs';

function formatBadge(count: number): string | undefined {
  if (count <= 0) {
    return undefined;
  }
  return count > 99 ? '99+' : String(count);
}

function menuContainsPath(menu: MenuRecordRaw, path: string): boolean {
  if (menu.path === path) {
    return true;
  }
  return Boolean(menu.children?.some((child) => menuContainsPath(child, path)));
}

function clearBadge(menu: MenuRecordRaw): MenuRecordRaw {
  return {
    ...menu,
    badge: undefined,
    badgeType: undefined,
    badgeVariants: undefined,
  };
}

/** 叶子显示未读数字；祖先（如「监控中心」）仅显示小红点 */
function applyBadgeFields(
  menu: MenuRecordRaw,
  badge: string | undefined,
  asDot: boolean,
): MenuRecordRaw {
  if (!badge) {
    return clearBadge(menu);
  }

  if (asDot) {
    return {
      ...menu,
      badge: undefined,
      badgeType: 'dot',
      badgeVariants: 'destructive',
    };
  }

  return {
    ...menu,
    badge,
    badgeType: 'normal',
    badgeVariants: 'destructive',
  };
}

function applyBadgeToMenus(
  menus: MenuRecordRaw[],
  badge: string | undefined,
): MenuRecordRaw[] {
  return menus.map((menu) => {
    const children = menu.children
      ? applyBadgeToMenus(menu.children, badge)
      : undefined;

    const next: MenuRecordRaw = {
      ...menu,
      children,
    };

    if (menu.path === EXCEPTION_LOG_MENU_PATH) {
      return applyBadgeFields(next, badge, false);
    }

    if (menuContainsPath(next, EXCEPTION_LOG_MENU_PATH)) {
      return applyBadgeFields(next, badge, true);
    }

    return next;
  });
}

export async function refreshExceptionLogMenuBadge(
  hasPermission: (code: string) => boolean,
): Promise<void> {
  const accessStore = useAccessStore();
  if (
    !hasPermission(MONITOR_EXCEPTION_LOG_PERMISSIONS.MANAGE) &&
    !hasPermission(MONITOR_EXCEPTION_LOG_PERMISSIONS.QUERY)
  ) {
    accessStore.setAccessMenus(
      applyBadgeToMenus(accessStore.accessMenus, undefined),
    );
    return;
  }

  try {
    const { count } = await fetchExceptionLogUnreadCount();
    accessStore.setAccessMenus(
      applyBadgeToMenus(accessStore.accessMenus, formatBadge(count)),
    );
  } catch {
    // 忽略徽章刷新失败，避免影响主流程
  }
}
