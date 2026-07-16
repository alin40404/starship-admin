/**
 * 插件后台前端面板登记：扫描 src/plugins/{slug}/panel.ts。
 * 开发态源码在管理端；导出包目录名为 backend/。
 */
import type { Component } from 'vue';

import type { PluginPanelModule } from './types';

import {
  PLUGIN_FRONTEND_DYNAMIC_ENABLED,
  listDeclaredPluginFrontendSlugs,
} from './frontend-registry';

const panelLoaders = import.meta.glob<{ default: PluginPanelModule }>(
  './*/panel.ts',
);

const registry = new Map<string, Component>();
const loadedSlugs = new Set<string>();
let preloadPromise: Promise<void> | null = null;

async function registerPanelsFromModule(mod: {
  default: PluginPanelModule;
}): Promise<void> {
  for (const panel of mod.default?.panels ?? []) {
    if (panel.id && panel.component) {
      registry.set(panel.id, panel.component as Component);
    }
  }
}

export async function loadPluginPanels(slug: string): Promise<void> {
  if (loadedSlugs.has(slug)) {
    return;
  }

  const path = `./${slug}/panel.ts`;
  const loader = panelLoaders[path];
  if (!loader) {
    loadedSlugs.add(slug);
    return;
  }

  await registerPanelsFromModule(await loader());
  loadedSlugs.add(slug);
}

async function preloadAllPanels(): Promise<void> {
  const slugs = new Set([
    ...listDeclaredPluginFrontendSlugs(),
    ...Object.keys(panelLoaders).map(
      (path) => path.slice(2, -'/panel.ts'.length),
    ),
  ]);

  await Promise.all([...slugs].map((slug) => loadPluginPanels(slug)));
}

export function ensurePluginPanelsPreloaded(): Promise<void> {
  if (PLUGIN_FRONTEND_DYNAMIC_ENABLED) {
    return Promise.resolve();
  }

  if (!preloadPromise) {
    preloadPromise = preloadAllPanels();
  }

  return preloadPromise;
}

export async function resolvePluginCustomComponentAsync(
  componentId: string,
  slug?: string,
): Promise<Component | null> {
  if (registry.has(componentId)) {
    return registry.get(componentId) ?? null;
  }

  if (PLUGIN_FRONTEND_DYNAMIC_ENABLED) {
    if (!slug) {
      return null;
    }
    await loadPluginPanels(slug);
  } else {
    await ensurePluginPanelsPreloaded();
  }

  return registry.get(componentId) ?? null;
}

/** @deprecated 优先使用 resolvePluginCustomComponentAsync */
export function resolvePluginCustomComponent(
  componentId: string,
): Component | null {
  return registry.get(componentId) ?? null;
}

export function listRegisteredPluginComponents(): string[] {
  return [...registry.keys()];
}
