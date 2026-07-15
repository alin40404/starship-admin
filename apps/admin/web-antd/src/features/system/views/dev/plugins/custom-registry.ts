/**
 * 插件自定义配置组件登记表（Wave2，编译期引入）。
 * Wave4 运行时动态加载前：新插件在此登记 component id → 异步组件。
 */
import type { Component } from 'vue';

import { defineAsyncComponent } from 'vue';

export type PluginCustomComponentLoader = () => Promise<{ default: Component }>;

const registry: Record<string, PluginCustomComponentLoader> = {
  'mail-smtp-advanced': () => import('@plugins/MailSmtp/admin/advanced-panel.vue'),
};

export function resolvePluginCustomComponent(componentId: string) {
  const loader = registry[componentId];
  if (!loader) {
    return null;
  }

  return defineAsyncComponent(loader);
}

export function listRegisteredPluginComponents(): string[] {
  return Object.keys(registry);
}
