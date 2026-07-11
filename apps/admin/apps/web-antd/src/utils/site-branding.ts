import { VBEN_LOGO_URL } from '@vben/constants';
import { useAppConfig } from '@vben/hooks';
import { preferences, updatePreferences } from '@vben/preferences';

import { overridesPreferences } from '#/preferences';

export interface SiteBranding {
  beian: string;
  defaultHomePath: string;
  favicon: string;
  logo: string;
  name: string;
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

/** 同步浏览器标题；Loading 仅更新站名文案，保留默认动画 */
export function applySiteBrandingToDocument(name: string): void {
  const displayName = name.trim();
  if (!displayName) {
    return;
  }

  document.title = displayName;

  const titleEl = document.querySelector('#__app-loading__ .title');
  if (titleEl) {
    titleEl.textContent = displayName;
  }
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
