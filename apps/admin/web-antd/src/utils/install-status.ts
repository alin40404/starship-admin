import { useAppConfig } from '@vben/hooks';

let cached: boolean | null = null;
let inflight: Promise<boolean | null> | null = null;

export function getCachedInstallStatus(): boolean | null {
  return cached;
}

export function setCachedInstallStatus(installed: boolean) {
  cached = installed;
}

export function clearCachedInstallStatus() {
  cached = null;
  inflight = null;
}

function isOnInstallPath(pathname = window.location.pathname, hash = window.location.hash): boolean {
  if (pathname === '/install' || pathname.endsWith('/install')) {
    return true;
  }
  const normalizedHash = hash.startsWith('#') ? hash.slice(1) : hash;
  return normalizedHash === '/install' || normalizedHash.startsWith('/install?');
}

/**
 * 未安装时把浏览器地址改到 /install，供首屏路由读取（避免先闪登录页）。
 */
export function ensureBrowserOnInstallPath(): void {
  if (isOnInstallPath()) {
    return;
  }

  const base = (import.meta.env.VITE_BASE || '/').replace(/\/$/, '') || '';
  const target =
    import.meta.env.VITE_ROUTER_HISTORY === 'hash'
      ? `${base}/#/install`
      : `${base}/install`;

  window.history.replaceState(window.history.state, '', target);
}

/**
 * 查询是否已安装。使用原生 fetch，避免未安装/后端未就绪时弹出业务错误 toast。
 * 网络失败时返回 null（不强制跳转安装页）。
 */
export async function resolveInstallStatus(force = false): Promise<boolean | null> {
  if (!force && cached !== null) {
    return cached;
  }

  if (!force && inflight) {
    return inflight;
  }

  inflight = (async () => {
    try {
      const { apiURL } = useAppConfig(import.meta.env, import.meta.env.PROD);
      const response = await fetch(`${apiURL}/install/status`, {
        headers: { Accept: 'application/json' },
      });
      if (!response.ok) {
        return null;
      }

      const payload = (await response.json()) as {
        code?: number;
        data?: { installed?: boolean };
      };
      if (payload?.code !== 0 || !payload?.data) {
        return null;
      }

      cached = Boolean(payload.data.installed);
      return cached;
    } catch {
      return null;
    } finally {
      inflight = null;
    }
  })();

  return inflight;
}
