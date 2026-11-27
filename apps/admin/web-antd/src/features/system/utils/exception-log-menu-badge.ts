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

function applyBadgeToMenus(
  menus: MenuRecordRaw[],
  badge: string | undefined,
): MenuRecordRaw[] {
  return menus.map((menu) => {
    const next: MenuRecordRaw = {
      ...menu,
      children: menu.children
        ? applyBadgeToMenus(menu.children, badge)
        : undefined,
    };

    if (menu.path === EXCEPTION_LOG_MENU_PATH) {
      next.badge = badge;
      next.badgeType = badge ? 'normal' : undefined;
      next.badgeVariants = badge ? 'destructive' : undefined;
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
