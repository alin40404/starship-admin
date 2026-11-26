import type { Router } from 'vue-router';

import { LOGIN_PATH } from '@vben/constants';
import { preferences } from '@vben/preferences';
import { useAccessStore, useUserStore } from '@vben/stores';
import { startProgress, stopProgress } from '@vben/utils';

import { accessRoutes, coreRouteNames } from '#/router/routes';
import { INSTALL_PATH } from '#/router/routes/core';
import { useAuthStore } from '#/store';
import { resolveInstallStatus } from '#/utils/install-status';

import { generateAccess } from './access';

function isInstallRoute(path: string, name?: string | symbol | null): boolean {
  return path === INSTALL_PATH || name === 'Install';
}

/**
 * 通用守卫配置
 * @param router
 */
function setupCommonGuard(router: Router) {
  // 记录已经加载的页面
  const loadedPaths = new Set<string>();

  router.beforeEach((to) => {
    to.meta.loaded = loadedPaths.has(to.path);

    // 页面加载进度条
    if (!to.meta.loaded && preferences.transition.progress) {
      startProgress();
    }
    return true;
  });

  router.afterEach((to) => {
    // 记录页面是否加载,如果已经加载，后续的页面切换动画等效果不在重复执行

    loadedPaths.add(to.path);

    // 关闭页面加载进度条
    if (preferences.transition.progress) {
      stopProgress();
    }
  });
}

/**
 * 未安装时强制进入安装引导；已安装则禁止再访问 /install。
 * 须在权限守卫之前注册，保证首跳优先进入安装页。
 */
function setupInstallGuard(router: Router) {
  router.beforeEach(async (to) => {
    const installed = await resolveInstallStatus();
    const onInstall = isInstallRoute(to.path, to.name as string | undefined);

    if (installed === false && !onInstall) {
      return {
        path: INSTALL_PATH,
        replace: true,
      };
    }

    if (installed === true && onInstall) {
      return {
        path: LOGIN_PATH,
        replace: true,
      };
    }

    return true;
  });
}

/**
 * 权限访问守卫配置
 * @param router
 */
function setupAccessGuard(router: Router) {
  router.beforeEach(async (to, from) => {
    const accessStore = useAccessStore();
    const userStore = useUserStore();
    const authStore = useAuthStore();

    // 安装页：跳过登录鉴权
    if (to.path === INSTALL_PATH || to.name === 'Install') {
      return true;
    }

    // 基本路由，这些路由不需要进入权限拦截
    if (coreRouteNames.includes(to.name as string)) {
      if (to.path === LOGIN_PATH && accessStore.accessToken) {
        return decodeURIComponent(
          (to.query?.redirect as string) ||
            userStore.userInfo?.homePath ||
            preferences.app.defaultHomePath,
        );
      }
      // Profile 等挂在 BasicLayout 下的 core 业务页：硬刷新时也要生成动态菜单，
      // 否则 accessMenus 为空，侧栏不显示。登录/认证相关 core 路由仍直接放行。
      const isAuthCoreRoute =
        to.name === 'Authentication' ||
        to.name === 'Root' ||
        String(to.path).startsWith('/auth');
      if (
        !accessStore.accessToken ||
        accessStore.isAccessChecked ||
        isAuthCoreRoute
      ) {
        return true;
      }
    }

    // accessToken 检查
    if (!accessStore.accessToken) {
      // 明确声明忽略权限访问权限，则可以访问
      if (to.meta.ignoreAccess) {
        return true;
      }

      // 没有访问权限，跳转登录页面
      if (to.fullPath !== LOGIN_PATH) {
        return {
          path: LOGIN_PATH,
          // 如不需要，直接删除 query
          query:
            to.fullPath === preferences.app.defaultHomePath
              ? {}
              : { redirect: encodeURIComponent(to.fullPath) },
          // 携带当前跳转的页面，登录后重新跳转该页面
          replace: true,
        };
      }
      return to;
    }

    // 是否已经生成过动态路由
    if (accessStore.isAccessChecked) {
      return true;
    }

    // 生成路由表
    // 当前登录用户拥有的角色标识列表
    const userInfo = userStore.userInfo || (await authStore.fetchUserInfo());
    const userRoles = userInfo.roles ?? [];

    // 生成菜单和路由
    const { accessibleMenus, accessibleRoutes } = await generateAccess({
      roles: userRoles,
      router,
      // 则会在菜单中显示，但是访问会被重定向到403
      routes: accessRoutes,
    });

    // 保存菜单信息和路由信息
    accessStore.setAccessMenus(accessibleMenus);
    accessStore.setAccessRoutes(accessibleRoutes);
    accessStore.setIsAccessChecked(true);
    const redirectPath = (from.query.redirect ??
      (to.path === preferences.app.defaultHomePath
        ? userInfo.homePath || preferences.app.defaultHomePath
        : to.fullPath)) as string;

    return {
      ...router.resolve(decodeURIComponent(redirectPath)),
      replace: true,
    };
  });
}

/**
 * 项目守卫配置
 * @param router
 */
function createRouterGuard(router: Router) {
  /** 通用 */
  setupCommonGuard(router);
  /** 安装引导 */
  setupInstallGuard(router);
  /** 权限访问 */
  setupAccessGuard(router);
}

export { createRouterGuard };
