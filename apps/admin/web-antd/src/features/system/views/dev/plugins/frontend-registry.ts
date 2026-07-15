/**
 * Wave4 预留：前端插件动态加载登记约定。
 *
 * 当前仅声明接口与占位清单；真正的 remote entry / 插件市场在 Wave2 菜单约定稳定后启用。
 * plugin.json 中可声明：
 *   "frontend": { "entry": "@plugins/Foo/admin", "routes": [] }
 *
 * Wave2 过渡：复杂 UI 通过 custom-registry.ts 编译期登记。
 * Wave4 目标：按已启用插件的 frontend.entry 动态 import，并合并 routes。
 */

export interface PluginFrontendManifest {
  entry?: string;
  routes?: Array<Record<string, unknown>>;
}

/** 运行时动态加载开关（默认关闭） */
export const PLUGIN_FRONTEND_DYNAMIC_ENABLED = false;

/** 已声明 frontend 的样例插件（文档/探测用，不自动加载） */
export const PLUGIN_FRONTEND_DECLARED: Record<string, PluginFrontendManifest> = {
  'mail-smtp': {
    entry: '@plugins/MailSmtp/admin',
    routes: [],
  },
};

export function getDeclaredPluginFrontend(
  slug: string,
): PluginFrontendManifest | null {
  return PLUGIN_FRONTEND_DECLARED[slug] ?? null;
}
