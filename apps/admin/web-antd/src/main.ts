import { initPreferences, updatePreferences } from '@vben/preferences';
import { unmountGlobalLoading } from '@vben/utils';

import { overridesPreferences, preferencesExtension } from './preferences';
import { initTimezone } from './timezone-init';
import { applySiteBranding } from './utils/site-branding';
import {
  applyAdminApiTimeout,
  initAdminRuntimeConfig,
} from './utils/admin-runtime-config';

/**
 * 应用初始化完成之后再进行页面加载渲染
 */
async function initApplication() {
  // name用于指定项目唯一标识
  // 用于区分不同项目的偏好设置以及存储数据的key前缀以及其他一些需要隔离的数据
  const env = import.meta.env.PROD ? 'prod' : 'dev';
  const appVersion = import.meta.env.VITE_APP_VERSION;
  const namespace = `${import.meta.env.VITE_APP_NAMESPACE}-${appVersion}-${env}`;

  // app偏好设置初始化
  await Promise.all([initPreferences({
    extension: preferencesExtension,
    namespace,
    overrides: overridesPreferences,
  }), initAdminRuntimeConfig()]);

  const { requestClient, baseRequestClient } = await import('#/api/request');
  applyAdminApiTimeout(requestClient, baseRequestClient);

  // Vben 会把偏好写入 localStorage；历史缓存可能仍保留旧值（defu 合并不覆盖已存在字段），
  // 启动后强制采用项目约定配置。
  updatePreferences({
    app: {
      accessMode: overridesPreferences.app?.accessMode ?? 'backend',
      defaultHomePath: overridesPreferences.app?.defaultHomePath ?? '/dashboard/console',
      defaultAvatar:
        overridesPreferences.app?.defaultAvatar ?? '/default-avatar.png',
    },
    theme: {
      fontSize: overridesPreferences.theme?.fontSize ?? 16,
      mode: overridesPreferences.theme?.mode ?? 'light',
      radius: overridesPreferences.theme?.radius ?? '0.5',
    },
  });

  await applySiteBranding();

  initTimezone();

  // 启动应用并挂载
  // vue应用主要逻辑及视图
  const { bootstrap } = await import('./bootstrap');
  await bootstrap(namespace);

  // 移除并销毁loading
  unmountGlobalLoading();
}

initApplication();
