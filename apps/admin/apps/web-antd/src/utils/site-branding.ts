import { VBEN_LOGO_URL } from '@vben/constants';
import { useAppConfig } from '@vben/hooks';
import { updatePreferences } from '@vben/preferences';

import { overridesPreferences } from '#/preferences';

export interface SiteBranding {
  beian: string;
  logo: string;
  name: string;
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
 * 将基础配置中的 site.* 同步到 Vben preferences（侧栏、登录页、页脚备案等）。
 */
export async function applySiteBranding(): Promise<void> {
  const branding = await fetchSiteBranding();
  const defaultLogo = overridesPreferences.logo?.source ?? VBEN_LOGO_URL;
  const defaultName =
    overridesPreferences.app?.name ?? import.meta.env.VITE_APP_TITLE ?? '';

  const name = branding?.name?.trim() || defaultName;
  const logo = branding?.logo?.trim() || defaultLogo;
  const beian = branding?.beian?.trim() ?? '';

  updatePreferences({
    app: { name },
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
