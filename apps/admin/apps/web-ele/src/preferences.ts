import { defineOverridesPreferences } from '@vben/preferences';

/**
 * @description 项目配置文件
 * 只需要覆盖项目中的一部分配置，不需要的配置不用覆盖，会自动使用默认配置
 * !!! 更改配置后请清空缓存，否则可能不生效
 */
export const overridesPreferences = defineOverridesPreferences({
  // overrides
  app: {
    name: import.meta.env.VITE_APP_TITLE,
    /** 与 Starship 后端菜单、路由一致：由接口返回动态路由 */
    accessMode: 'backend',
    /** 与种子菜单「工作台 / 控制台」路径一致 */
    defaultHomePath: '/dashboard/console',
  },
});
