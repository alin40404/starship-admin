/**
 * 插件管理权限，与 modules/System/app/Support/PluginPermissions.php 保持一致。
 */
import { $t } from '#/locales';

export const PLUGIN_PERMISSIONS = {
  MANAGE: 'system.plugins',
  QUERY: 'system.plugins.query',
  DISCOVER: 'system.plugins.discover',
  INSTALL: 'system.plugins.install',
  ENABLE: 'system.plugins.enable',
  DISABLE: 'system.plugins.disable',
  UNINSTALL: 'system.plugins.uninstall',
  CONFIG: 'system.plugins.config',
} as const;

export type PluginPermissionCode =
  (typeof PLUGIN_PERMISSIONS)[keyof typeof PLUGIN_PERMISSIONS];

export const PLUGIN_STATUS = {
  DISCOVERED: 0,
  INSTALLED: 1,
  ENABLED: 2,
  DISABLED: 3,
} as const;

/** Ant Design Tag 传 hex 时为实心绿底白字 */
export const PLUGIN_STATUS_TAG_ENABLED = '#52c41a';

export function getPluginStatusTagColor(status: number): string {
  switch (status) {
    case PLUGIN_STATUS.ENABLED: {
      return PLUGIN_STATUS_TAG_ENABLED;
    }
    case PLUGIN_STATUS.INSTALLED: {
      return 'processing';
    }
    case PLUGIN_STATUS.DISABLED: {
      return 'warning';
    }
    default: {
      return 'default';
    }
  }
}

/** 按状态码取前端文案（勿用接口 status_label，后端为中文硬编码） */
export function getPluginStatusLabel(status: number): string {
  switch (status) {
    case PLUGIN_STATUS.ENABLED: {
      return $t('dev.plugins.statusEnabled');
    }
    case PLUGIN_STATUS.INSTALLED: {
      return $t('dev.plugins.statusInstalled');
    }
    case PLUGIN_STATUS.DISABLED: {
      return $t('dev.plugins.statusDisabled');
    }
    default: {
      return $t('dev.plugins.statusDiscovered');
    }
  }
}

export function getPluginStatusTagOptions() {
  return [
    {
      color: 'default',
      label: getPluginStatusLabel(PLUGIN_STATUS.DISCOVERED),
      value: PLUGIN_STATUS.DISCOVERED,
    },
    {
      color: 'processing',
      label: getPluginStatusLabel(PLUGIN_STATUS.INSTALLED),
      value: PLUGIN_STATUS.INSTALLED,
    },
    {
      color: PLUGIN_STATUS_TAG_ENABLED,
      label: getPluginStatusLabel(PLUGIN_STATUS.ENABLED),
      value: PLUGIN_STATUS.ENABLED,
    },
    {
      color: 'warning',
      label: getPluginStatusLabel(PLUGIN_STATUS.DISABLED),
      value: PLUGIN_STATUS.DISABLED,
    },
  ] as const;
}
