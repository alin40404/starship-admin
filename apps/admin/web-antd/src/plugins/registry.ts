/**
 * 插件后台前端面板登记：扫描 src/plugins/{slug}/panel.ts。
 * 开发态源码在管理端；导出包目录名为 backend/。
 */
import type { Component } from 'vue';

import type { PluginPanelModule } from './types';

const panelModules = import.meta.glob<{ default: PluginPanelModule }>(
  './*/panel.ts',
  { eager: true },
);

const registry = new Map<string, Component>();

for (const mod of Object.values(panelModules)) {
  const panels = mod.default?.panels ?? [];
  for (const panel of panels) {
    if (panel.id && panel.component) {
      registry.set(panel.id, panel.component as Component);
    }
  }
}

export function resolvePluginCustomComponent(
  componentId: string,
): Component | null {
  return registry.get(componentId) ?? null;
}

export function listRegisteredPluginComponents(): string[] {
  return [...registry.keys()];
}
