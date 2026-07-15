/**
 * Wave4 预留：前端插件动态加载。
 * 当前 L2 面板走 registry.ts（compile-time glob）；本开关默认关闭。
 */

export interface PluginFrontendManifest {
  entry?: string;
  routes?: Array<Record<string, unknown>>;
}

export const PLUGIN_FRONTEND_DYNAMIC_ENABLED = false;

export const PLUGIN_FRONTEND_DECLARED: Record<string, PluginFrontendManifest> =
  {
    'mail-smtp': {
      entry: '#/plugins/mail-smtp',
      routes: [],
    },
  };

export function getDeclaredPluginFrontend(
  slug: string,
): PluginFrontendManifest | null {
  return PLUGIN_FRONTEND_DECLARED[slug] ?? null;
}
