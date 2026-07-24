import type { Recordable, UserInfo } from '@vben/types';

import { ref } from 'vue';

import { LOGIN_PATH } from '@vben/constants';
import { preferences } from '@vben/preferences';
import { resetAllStores, useAccessStore, useUserStore } from '@vben/stores';

import { notification } from 'ant-design-vue';
import { defineStore } from 'pinia';

import { getAccessCodesApi, getUserInfoApi, loginApi, logoutApi, smsLoginApi } from '#/api';
import { $t } from '#/locales';
import { router } from '#/router';
import { refreshAccess as refreshAccessFromServer } from '#/utils/refresh-access';

export const useAuthStore = defineStore('auth', () => {
  const accessStore = useAccessStore();
  const userStore = useUserStore();

  const loginLoading = ref(false);
  /** 防止并发 401 重复触发 logout / 跳转死循环 */
  const isLoggingOut = ref(false);

  function notifyLoginSuccess(realName: string) {
    notification.success({
      description: `${$t('authentication.loginSuccessDesc')}:${realName}`,
      duration: 3,
      message: $t('authentication.loginSuccess'),
    });
  }

  /**
   * 异步处理登录操作
   * Asynchronously handle the login process
   * @param params 登录表单数据
   */
  async function authLogin(
    params: Recordable<any>,
    onSuccess?: () => Promise<void> | void,
  ) {
    // 异步处理用户登录操作并获取 accessToken
    let userInfo: null | UserInfo = null;
    try {
      loginLoading.value = true;
      const { accessToken } = await loginApi(params);

      // 如果成功获取到 accessToken
      if (accessToken) {
        accessStore.setAccessToken(accessToken);

        // 获取用户信息并存储到 accessStore 中
        const [fetchUserInfoResult, accessCodes] = await Promise.all([
          fetchUserInfo(),
          getAccessCodesApi(),
        ]);

        userInfo = fetchUserInfoResult;

        userStore.setUserInfo(userInfo);
        accessStore.setAccessCodes(accessCodes);

        if (accessStore.loginExpired) {
          accessStore.setLoginExpired(false);
        } else {
          onSuccess
            ? await onSuccess?.()
            : await router.push(
                userInfo.homePath || preferences.app.defaultHomePath,
              );
        }

        if (userInfo?.realName) {
          notifyLoginSuccess(userInfo.realName);
        }
      }
    } finally {
      loginLoading.value = false;
    }

    return {
      userInfo,
    };
  }

  async function authSmsLogin(
    params: { mobile: string; code: string },
    onSuccess?: () => Promise<void> | void,
  ) {
    let userInfo: null | UserInfo = null;
    try {
      loginLoading.value = true;
      const { accessToken } = await smsLoginApi(params);

      if (accessToken) {
        accessStore.setAccessToken(accessToken);

        const [fetchUserInfoResult, accessCodes] = await Promise.all([
          fetchUserInfo(),
          getAccessCodesApi(),
        ]);

        userInfo = fetchUserInfoResult;

        userStore.setUserInfo(userInfo);
        accessStore.setAccessCodes(accessCodes);

        if (accessStore.loginExpired) {
          accessStore.setLoginExpired(false);
        } else {
          onSuccess
            ? await onSuccess?.()
            : await router.push(
                userInfo.homePath || preferences.app.defaultHomePath,
              );
        }

        if (userInfo?.realName) {
          notifyLoginSuccess(userInfo.realName);
        }
      }
    } finally {
      loginLoading.value = false;
    }

    return {
      userInfo,
    };
  }

  async function logout(redirect: boolean = true) {
    if (isLoggingOut.value) {
      return;
    }
    isLoggingOut.value = true;

    const redirectPath = router.currentRoute.value.fullPath;

    try {
      await logoutApi();
    } catch {
      // 会话已失效时退出接口可能失败，忽略
    } finally {
      resetAllStores();
      accessStore.setLoginExpired(false);
    }

    try {
      // 回登录页带上当前路由地址
      await router.replace({
        path: LOGIN_PATH,
        query: redirect
          ? {
              redirect: encodeURIComponent(redirectPath),
            }
          : {},
      });
    } finally {
      isLoggingOut.value = false;
    }
  }

  async function fetchUserInfo() {
    const userInfo = await getUserInfoApi();
    userStore.setUserInfo(userInfo);
    return userInfo;
  }

  async function refreshAccess() {
    return refreshAccessFromServer();
  }

  function $reset() {
    loginLoading.value = false;
  }

  return {
    $reset,
    authLogin,
    authSmsLogin,
    fetchUserInfo,
    loginLoading,
    logout,
    refreshAccess,
  };
});
