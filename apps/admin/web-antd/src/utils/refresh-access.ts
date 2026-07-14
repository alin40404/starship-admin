import { useAccessStore, useUserStore } from '@vben/stores';

import { getAccessCodesApi } from '#/api';
import { generateAccess } from '#/router/access';
import { router } from '#/router';
import { accessRoutes } from '#/router/routes';

/**
 * 重新拉取权限码与菜单，并重建动态路由（无需退出登录）。
 */
export async function refreshAccess() {
  const accessStore = useAccessStore();
  const userStore = useUserStore();

  const accessCodes = await getAccessCodesApi();
  accessStore.setAccessCodes(accessCodes);

  const roles = userStore.userInfo?.roles ?? [];

  accessStore.setIsAccessChecked(false);

  const { accessibleMenus, accessibleRoutes } = await generateAccess({
    roles,
    router,
    routes: accessRoutes,
  });

  accessStore.setAccessMenus(accessibleMenus);
  accessStore.setAccessRoutes(accessibleRoutes);
  accessStore.setIsAccessChecked(true);

  return { accessCodes, accessibleMenus };
}
