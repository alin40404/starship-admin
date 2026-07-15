/**
 * 主题管理权限，与 modules/System/app/Support/ThemePermissions.php 保持一致。
 */
import { $t } from '#/locales';

export const THEME_PERMISSIONS = {
  MANAGE: 'system.themes',
  QUERY: 'system.themes.query',
  DISCOVER: 'system.themes.discover',
  INSTALL: 'system.themes.install',
  ENABLE: 'system.themes.enable',
  DISABLE: 'system.themes.disable',
  UNINSTALL: 'system.themes.uninstall',
  SWITCH: 'system.themes.switch',
} as const;

export type ThemePermissionCode =
  (typeof THEME_PERMISSIONS)[keyof typeof THEME_PERMISSIONS];

export const THEME_STATUS = {
  DISCOVERED: 0,
  INSTALLED: 1,
  ENABLED: 2,
  DISABLED: 3,
} as const;

/** Ant Design Tag 传 hex 时为实心绿底白字 */
export const THEME_STATUS_TAG_ENABLED = '#52c41a';

export function getThemeStatusTagColor(status: number): string {
  switch (status) {
    case THEME_STATUS.ENABLED: {
      return THEME_STATUS_TAG_ENABLED;
    }
    case THEME_STATUS.INSTALLED: {
      return 'processing';
    }
    case THEME_STATUS.DISABLED: {
      return 'warning';
    }
    default: {
      return 'default';
    }
  }
}

/** 按状态码取前端文案（勿用接口 status_label，后端为中文硬编码） */
export function getThemeStatusLabel(status: number): string {
  switch (status) {
    case THEME_STATUS.ENABLED: {
      return $t('dev.themes.statusEnabled');
    }
    case THEME_STATUS.INSTALLED: {
      return $t('dev.themes.statusInstalled');
    }
    case THEME_STATUS.DISABLED: {
      return $t('dev.themes.statusDisabled');
    }
    default: {
      return $t('dev.themes.statusDiscovered');
    }
  }
}

export function getThemeStatusTagOptions() {
  return [
    {
      color: 'default',
      label: getThemeStatusLabel(THEME_STATUS.DISCOVERED),
      value: THEME_STATUS.DISCOVERED,
    },
    {
      color: 'processing',
      label: getThemeStatusLabel(THEME_STATUS.INSTALLED),
      value: THEME_STATUS.INSTALLED,
    },
    {
      color: THEME_STATUS_TAG_ENABLED,
      label: getThemeStatusLabel(THEME_STATUS.ENABLED),
      value: THEME_STATUS.ENABLED,
    },
    {
      color: 'warning',
      label: getThemeStatusLabel(THEME_STATUS.DISABLED),
      value: THEME_STATUS.DISABLED,
    },
  ] as const;
}
