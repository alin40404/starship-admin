import { initPreferences, updatePreferences } from '@vben/preferences';
import { unmountGlobalLoading } from '@vben/utils';

import { overridesPreferences } from './preferences';

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
  await initPreferences({
    namespace,
    overrides: overridesPreferences,
  });

  // Vben 会把偏好写入 localStorage；历史缓存里的 accessMode 可能仍是默认的 frontend，
  // 会覆盖 overrides 中的 backend，导致侧栏仍显示模板自带的「概览/演示/项目」而非后端菜单。
  // 启动后强制采用项目约定的 accessMode / 首页路径。
  updatePreferences({
    app: {
      accessMode: overridesPreferences.app?.accessMode ?? 'backend',
      defaultHomePath: overridesPreferences.app?.defaultHomePath ?? '/dashboard',
    },
  });

  // 启动应用并挂载
  // vue应用主要逻辑及视图
  const { bootstrap } = await import('./bootstrap');
  await bootstrap(namespace);

  // 移除并销毁loading
  unmountGlobalLoading();
}

initApplication();
