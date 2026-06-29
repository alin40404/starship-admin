import type { Recordable } from '@vben/types';

import type { IconifyJSON } from '@iconify/vue/offline';

import { addCollection } from '@iconify/vue/offline';

import {
  FULL_ICON_JSON_LOADERS,
  FULL_OFFLINE_ICON_PREFIXES,
  SPARSE_ICON_COLLECTIONS,
  SPARSE_OFFLINE_ICON_PREFIXES,
} from './loaders';

/** 图标选择器等组件使用的本地缓存（刷新页面前有效） */
export const ICONS_MAP: Recordable<string[]> = {};

let initPromise: null | Promise<void> = null;
let initialized = false;

function registerCollection(data: IconifyJSON) {
  const prefix = data.prefix;
  if (!prefix || !data.icons) {
    return;
  }

  addCollection(data);

  const list = Object.keys(data.icons).map((name) => `${prefix}:${name}`);
  ICONS_MAP[prefix] = list;
}

async function loadOfflineIconSets() {
  await Promise.all(
    FULL_OFFLINE_ICON_PREFIXES.map(async (prefix) => {
      const loader = FULL_ICON_JSON_LOADERS[prefix];
      if (!loader) {
        console.warn(`[offline-icons] Missing loader for prefix: ${prefix}`);
        return;
      }

      const mod = await loader();
      registerCollection(mod.default);
    }),
  );

  for (const prefix of SPARSE_OFFLINE_ICON_PREFIXES) {
    const data = SPARSE_ICON_COLLECTIONS[prefix];
    if (data) {
      registerCollection(data);
    }
  }
}

/** 预加载本地 Iconify 图标集，避免运行时请求 api.iconify.design */
export function initOfflineIcons(): Promise<void> {
  if (initialized) {
    return Promise.resolve();
  }

  if (!initPromise) {
    initPromise = loadOfflineIconSets()
      .then(() => {
        initialized = true;
      })
      .catch((error) => {
        initPromise = null;
        console.error('[offline-icons] Failed to load icon sets:', error);
        throw error;
      });
  }

  return initPromise;
}

/** 列出已离线注册的图标（供图标选择器使用） */
export function listIcons(_provider?: string, prefix?: string): string[] {
  if (prefix) {
    return ICONS_MAP[prefix] ?? [];
  }

  return Object.values(ICONS_MAP).flat();
}

/**
 * 获取指定前缀的图标列表（本地数据，不再请求 Iconify API）
 */
export async function fetchIconsData(prefix: string): Promise<string[]> {
  await initOfflineIcons();
  return ICONS_MAP[prefix] ?? [];
}
