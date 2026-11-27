import type {
  ComponentRecordType,
  GenerateMenuAndRoutesOptions,
} from '@vben/types';

import { generateAccessible } from '@vben/access';
import { preferences } from '@vben/preferences';

import { message } from 'ant-design-vue';

import { getAllMenusApi } from '#/api';
import { BasicLayout, IFrameView } from '#/layouts';
import { $t } from '#/locales';

const forbiddenComponent = () => import('#/views/_core/fallback/forbidden.vue');

/**
 * Vben normalizeViewPath 会去掉路径前缀 `/views`。
 * features 下页面需映射成等价的 `../views/...` key，菜单 component 才继续有效。
 *
 * 同时注册两种 key，兼容不同模块的目录习惯：
 * - `features/system/views/permission/...` → `/permission/...`（system 不带模块名）
 * - `features/member/views/member/...` → `/member/...`（相对路径已含模块名，不重复拼接）
 * - 其它业务 feature（如 `features/xxx/views/...`）→ `/xxx/...` 与相对路径两种 key
 */
function buildPageMap(): ComponentRecordType {
  const pageMap: ComponentRecordType = {
    ...import.meta.glob('../views/**/*.vue'),
  };

  const featureViews = import.meta.glob('../features/*/views/**/*.vue');
  for (const [key, loader] of Object.entries(featureViews)) {
    const matched = key.match(/\/features\/([^/]+)\/views\/(.+)$/);
    if (!matched?.[1] || !matched[2]) {
      continue;
    }
    const feature = matched[1];
    const relative = matched[2];
    const typedLoader = loader as ComponentRecordType[string];

    pageMap[`../views/${relative}`] = typedLoader;

    if (!relative.startsWith(`${feature}/`)) {
      pageMap[`../views/${feature}/${relative}`] = typedLoader;
    }
  }

  return pageMap;
}

async function generateAccess(options: GenerateMenuAndRoutesOptions) {
  const pageMap = buildPageMap();

  const layoutMap: ComponentRecordType = {
    BasicLayout,
    IFrameView,
  };

  return await generateAccessible(preferences.app.accessMode, {
    ...options,
    fetchMenuListAsync: async () => {
      message.loading({
        content: `${$t('common.loadingMenu')}...`,
        duration: 1.5,
      });
      return await getAllMenusApi();
    },
    // 可以指定没有权限跳转403页面
    forbiddenComponent,
    // 如果 route.meta.menuVisibleWithForbidden = true
    layoutMap,
    pageMap,
  });
}

export { generateAccess };
