import { addAPIProvider } from '@iconify/vue';

const DEFAULT_ICONIFY_API = 'https://api.iconify.design';

export interface IconifyApiEndpoint {
  /** 完整 API 根路径，如 https://cdn.example.com/iconify */
  base: string;
  /** addAPIProvider 使用的 origin */
  origin: string;
  /** addAPIProvider 使用的 path，位于根路径时为 `/` */
  path: string;
}

function parseIconifyApiUrl(raw: string): IconifyApiEndpoint | null {
  const value = raw.trim();
  if (!value) {
    return null;
  }

  try {
    const url = new URL(value);
    const path =
      url.pathname === '/' || url.pathname === ''
        ? '/'
        : url.pathname.endsWith('/')
          ? url.pathname
          : `${url.pathname}/`;
    const base =
      path === '/' ? url.origin : `${url.origin}${path.replace(/\/$/, '')}`;

    return {
      base,
      origin: url.origin,
      path,
    };
  } catch {
    console.warn('[iconify] Invalid API URL:', value);
    return null;
  }
}

/** 解析 VITE_ICONIFY_API，支持逗号分隔主备地址 */
export function getIconifyApiEndpoints(): IconifyApiEndpoint[] {
  const env = import.meta.env.VITE_ICONIFY_API as string | undefined;

  if (!env?.trim()) {
    const fallback = parseIconifyApiUrl(DEFAULT_ICONIFY_API);
    return fallback ? [fallback] : [];
  }

  const endpoints = env
    .split(',')
    .map((item) => parseIconifyApiUrl(item))
    .filter((item): item is IconifyApiEndpoint => item !== null);

  if (endpoints.length > 0) {
    return endpoints;
  }

  const fallback = parseIconifyApiUrl(DEFAULT_ICONIFY_API);
  return fallback ? [fallback] : [];
}

/** 构建图标集列表接口地址 */
export function buildIconifyCollectionUrl(base: string, prefix: string): string {
  const normalizedBase = base.endsWith('/') ? base.slice(0, -1) : base;
  return `${normalizedBase}/collection?prefix=${encodeURIComponent(prefix)}`;
}

function setupIconifyApiProvider() {
  const env = import.meta.env.VITE_ICONIFY_API as string | undefined;
  if (!env?.trim()) {
    return;
  }

  const endpoints = getIconifyApiEndpoints();
  if (endpoints.length === 0) {
    return;
  }

  const [first] = endpoints;

  try {
    addAPIProvider('', {
      resources: endpoints.map((item) => item.origin),
      ...(first && first.path !== '/' ? { path: first.path } : {}),
    });
  } catch (error) {
    console.warn('[iconify] Failed to configure API provider:', error);
  }
}

setupIconifyApiProvider();
