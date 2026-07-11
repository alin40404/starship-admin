import type { RequestClient } from '@vben/request';

import { useAppConfig } from '@vben/hooks';

export const ADMIN_PAGE_SIZE_DEFAULT = 20;
export const ADMIN_ATTACHMENT_FLOW_PAGE_SIZE_DEFAULT = 50;
export const ADMIN_API_TIMEOUT_SECONDS_DEFAULT = 30;

export interface AdminRuntimeSnapshot {
  apiTimeoutSeconds: number;
  attachmentFlowPageSize: number;
  loginCaptcha: boolean;
  maintenance: boolean;
  pageSize: number;
}

const DEFAULT_SNAPSHOT: AdminRuntimeSnapshot = {
  apiTimeoutSeconds: ADMIN_API_TIMEOUT_SECONDS_DEFAULT,
  attachmentFlowPageSize: ADMIN_ATTACHMENT_FLOW_PAGE_SIZE_DEFAULT,
  loginCaptcha: false,
  maintenance: false,
  pageSize: ADMIN_PAGE_SIZE_DEFAULT,
};

let snapshot: AdminRuntimeSnapshot = { ...DEFAULT_SNAPSHOT };
let loaded = false;

function parsePositiveInt(value: unknown, fallback: number): number {
  const parsed = Number.parseInt(String(value ?? ''), 10);

  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

function parseBool(value: unknown, fallback = false): boolean {
  if (typeof value === 'boolean') {
    return value;
  }

  const normalized = String(value ?? '').trim().toLowerCase();

  if (['1', 'true', 'yes', 'on'].includes(normalized)) {
    return true;
  }

  if (['0', 'false', 'no', 'off', ''].includes(normalized)) {
    return false;
  }

  return fallback;
}

function normalizeSnapshot(data: Record<string, unknown>): AdminRuntimeSnapshot {
  return {
    pageSize: parsePositiveInt(data.pageSize, ADMIN_PAGE_SIZE_DEFAULT),
    attachmentFlowPageSize: parsePositiveInt(
      data.attachmentFlowPageSize,
      ADMIN_ATTACHMENT_FLOW_PAGE_SIZE_DEFAULT,
    ),
    apiTimeoutSeconds: parsePositiveInt(
      data.apiTimeoutSeconds,
      ADMIN_API_TIMEOUT_SECONDS_DEFAULT,
    ),
    loginCaptcha: parseBool(data.loginCaptcha, false),
    maintenance: parseBool(data.maintenance, false),
  };
}

export async function initAdminRuntimeConfig(
  force = false,
): Promise<AdminRuntimeSnapshot> {
  if (loaded && !force) {
    return snapshot;
  }

  const { apiURL } = useAppConfig(import.meta.env, import.meta.env.PROD);

  try {
    const response = await fetch(`${apiURL}/system/runtime`, {
      headers: { Accept: 'application/json' },
    });
    if (!response.ok) {
      snapshot = { ...DEFAULT_SNAPSHOT };
    } else {
      const payload = await response.json();
      snapshot =
        payload?.code === 0 && payload?.data
          ? normalizeSnapshot(payload.data as Record<string, unknown>)
          : { ...DEFAULT_SNAPSHOT };
    }
  } catch {
    snapshot = { ...DEFAULT_SNAPSHOT };
  }

  loaded = true;

  return snapshot;
}

export function getAdminRuntimeSnapshot(): AdminRuntimeSnapshot {
  return snapshot;
}

export function clearAdminRuntimeParamsCache(): void {
  snapshot = { ...DEFAULT_SNAPSHOT };
  loaded = false;
}

export function getAdminPageSizeSync(): number {
  return snapshot.pageSize;
}

export function getAdminAttachmentFlowPageSizeSync(): number {
  return snapshot.attachmentFlowPageSize;
}

export function getAdminApiTimeoutSecondsSync(): number {
  return snapshot.apiTimeoutSeconds;
}

export function isAdminLoginCaptchaEnabled(): boolean {
  return snapshot.loginCaptcha;
}

export function isAdminMaintenanceEnabled(): boolean {
  return snapshot.maintenance;
}

export async function getAdminPageSize(): Promise<number> {
  await initAdminRuntimeConfig();

  return snapshot.pageSize;
}

export async function getAdminAttachmentFlowPageSize(): Promise<number> {
  await initAdminRuntimeConfig();

  return snapshot.attachmentFlowPageSize;
}

export function applyAdminApiTimeout(...clients: RequestClient[]): void {
  const timeoutMs = snapshot.apiTimeoutSeconds * 1000;

  for (const client of clients) {
    client.instance.defaults.timeout = timeoutMs;
  }
}
