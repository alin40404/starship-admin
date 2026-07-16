/**
 * Wave4：前端插件动态加载约定。
 * L2 面板源码在 src/plugins/{slug}/panel.ts；导出包目录名为 backend/。
 * 开关关闭时启动预加载全部 panel；开启时仅按 slug 按需加载。
 */

export interface PluginFrontendManifest {
  entry?: string;
  routes?: Array<Record<string, unknown>>;
}

/** 与 plugin.json.frontend 对齐；动态路由远期再扩展 */
export const PLUGIN_FRONTEND_DECLARED: Record<string, PluginFrontendManifest> =
  {
    'sms-aliyun': {
      entry: '#/plugins/sms-aliyun',
      routes: [],
    },
    'mail-smtp': {
      entry: '#/plugins/mail-smtp',
      routes: [],
    },
    'storage-oss': {
      entry: '#/plugins/storage-oss',
      routes: [],
    },
  };

export const PLUGIN_FRONTEND_DYNAMIC_ENABLED =
  import.meta.env.VITE_PLUGIN_FRONTEND_DYNAMIC_ENABLED === 'true';

export function getDeclaredPluginFrontend(
  slug: string,
): PluginFrontendManifest | null {
  return PLUGIN_FRONTEND_DECLARED[slug] ?? null;
}

export function listDeclaredPluginFrontendSlugs(): string[] {
  return Object.keys(PLUGIN_FRONTEND_DECLARED);
}
