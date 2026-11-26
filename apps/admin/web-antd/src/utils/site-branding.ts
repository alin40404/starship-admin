import { VBEN_LOGO_URL } from '@vben/constants';
import { useAppConfig } from '@vben/hooks';
import { preferences, updatePreferences } from '@vben/preferences';
import { useTimezoneStore } from '@vben/stores';
import { setCurrentTimezone } from '@vben/utils';
import { getActivePinia } from 'pinia';

import { overridesPreferences } from '#/preferences';

export const SITE_DISPLAY_TIMEZONE_DEFAULT = 'Asia/Shanghai';

export interface SiteBranding {
  beian: string;
  defaultHomePath: string;
  favicon: string;
  logo: string;
  name: string;
  timezone: string;
}

export function resolveSiteDisplayTimezone(timezone?: string): string {
  const value = timezone?.trim();
  if (!value) {
    return SITE_DISPLAY_TIMEZONE_DEFAULT;
  }

  try {
    Intl.DateTimeFormat(undefined, { timeZone: value });
    return value;
  } catch {
    return SITE_DISPLAY_TIMEZONE_DEFAULT;
  }
}

export function applySiteDisplayTimezone(timezone?: string): string {
  const resolved = resolveSiteDisplayTimezone(timezone);
  setCurrentTimezone(resolved);
  syncTimezoneStore(resolved);
  return resolved;
}

/** 同步 Pinia 展示值（不调 setTimezone，避免二次写入配置） */
function syncTimezoneStore(timezone: string) {
  if (!getActivePinia()) {
    return;
  }

  try {
    const store = useTimezoneStore();
    if (store.timezone !== timezone) {
      store.timezone = timezone;
    }
  } catch {
    // Pinia / store 尚未就绪时忽略
  }
}

function normalizeHomePath(path: string, fallback: string): string {
  const trimmed = path.trim();
  if (!trimmed) {
    return fallback;
  }
  return trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
}

export async function fetchSiteBranding(): Promise<SiteBranding | null> {
  const { apiURL } = useAppConfig(import.meta.env, import.meta.env.PROD);

  try {
    const response = await fetch(`${apiURL}/system/site`, {
      headers: { Accept: 'application/json' },
    });
    if (!response.ok) {
      return null;
    }
    const payload = await response.json();
    if (payload?.code !== 0 || !payload?.data) {
      return null;
    }
    return payload.data as SiteBranding;
  } catch {
    return null;
  }
}

/** 同步浏览器标题（首屏 Loading 不再展示站名） */
export function applySiteBrandingToDocument(name: string): void {
  const displayName = name.trim();
  if (!displayName) {
    return;
  }

  document.title = displayName;
}

function resolveDefaultBranding() {
  return {
    defaultHomePath:
      overridesPreferences.app?.defaultHomePath ?? '/dashboard/console',
    defaultLogo: overridesPreferences.logo?.source ?? VBEN_LOGO_URL,
    defaultName:
      overridesPreferences.app?.name ??
      import.meta.env.VITE_APP_TITLE ??
      '',
  };
}

/**
 * 将基础配置中的 site.* 同步到 Vben preferences（侧栏、登录页、页脚备案、默认主页等）。
 */
export async function applySiteBranding(): Promise<void> {
  const { defaultHomePath, defaultLogo, defaultName } = resolveDefaultBranding();

  // 先用本地 preferences，让 Loading / 标题在接口返回前尽量与上次一致
  applySiteBrandingToDocument(
    preferences.app?.name || defaultName,
  );

  const branding = await fetchSiteBranding();

  const name = branding?.name?.trim() || defaultName;
  const logo = branding?.logo?.trim() || defaultLogo;
  const beian = branding?.beian?.trim() ?? '';
  const homePath = normalizeHomePath(
    branding?.defaultHomePath ?? '',
    defaultHomePath,
  );

  applySiteDisplayTimezone(branding?.timezone);

  updatePreferences({
    app: { name, defaultHomePath: homePath },
    copyright: {
      companyName: name,
      companySiteLink: '',
      date: String(new Date().getFullYear()),
      enable: true,
      icp: beian,
    },
    logo: { source: logo },
  });

  applySiteBrandingToDocument(name);
}
