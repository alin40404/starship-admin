import { fetchInstallStatus } from '#/api/core/install';

let cached: boolean | null = null;
let inflight: Promise<boolean> | null = null;

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

/**
 * 查询是否已安装。网络失败时返回 null（不强制跳转安装页）。
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
      const status = await fetchInstallStatus();
      cached = Boolean(status?.installed);
      return cached;
    } catch {
      return null;
    } finally {
      inflight = null;
    }
  })();

  return inflight;
}
