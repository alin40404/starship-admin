import type { IconifyJSON } from '@iconify/vue/offline';

type IconJsonModule = { default: IconifyJSON };

/** Vite 需要静态 import 路径，才能正确分包 */
export const FULL_ICON_JSON_LOADERS: Record<
  string,
  () => Promise<IconJsonModule>
> = {
  'ant-design': () => import('@iconify/json/json/ant-design.json'),
  carbon: () => import('@iconify/json/json/carbon.json'),
  ep: () => import('@iconify/json/json/ep.json'),
  lucide: () => import('@iconify/json/json/lucide.json'),
};

/** 完整离线注册的图标集（如图标选择器需要全量列表） */
export const FULL_OFFLINE_ICON_PREFIXES = [
  'ant-design',
  'carbon',
  'ep',
  'lucide',
] as const;

/** 仅按需打包的稀疏图标集（见 sparse-collections.json） */
export const SPARSE_OFFLINE_ICON_PREFIXES = [
  'bx',
  'fluent-mdl2',
  'ic',
  'ion',
  'logos',
  'mdi',
] as const;

import sparseCollections from './sparse-collections.json';

export const SPARSE_ICON_COLLECTIONS = sparseCollections as Record<
  string,
  IconifyJSON
>;
