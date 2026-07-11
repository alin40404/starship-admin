import { VBEN_LOGO_URL } from '@vben/constants';
import { useAppConfig } from '@vben/hooks';
import { updatePreferences } from '@vben/preferences';

import { overridesPreferences } from '#/preferences';

export interface SiteBranding {
  beian: string;
  defaultHomePath: string;
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

/**
 * 将基础配置中的 site.* 同步到 Vben preferences（侧栏、登录页、页脚备案、默认主页等）。
 */
export async function applySiteBranding(): Promise<void> {
  const branding = await fetchSiteBranding();
  const defaultLogo = overridesPreferences.logo?.source ?? VBEN_LOGO_URL;
  const defaultName =
    overridesPreferences.app?.name ?? import.meta.env.VITE_APP_TITLE ?? '';
  const defaultHomePath =
    overridesPreferences.app?.defaultHomePath ?? '/dashboard/console';

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
}
