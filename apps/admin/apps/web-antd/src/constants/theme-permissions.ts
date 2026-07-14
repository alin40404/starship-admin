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

export function getThemeStatusTagOptions() {
  return [
    {
      color: 'default',
      label: $t('dev.themes.statusDiscovered'),
      value: THEME_STATUS.DISCOVERED,
    },
    {
      color: 'processing',
      label: $t('dev.themes.statusInstalled'),
      value: THEME_STATUS.INSTALLED,
    },
    {
      color: 'success',
      label: $t('dev.themes.statusEnabled'),
      value: THEME_STATUS.ENABLED,
    },
    {
      color: 'warning',
      label: $t('dev.themes.statusDisabled'),
      value: THEME_STATUS.DISABLED,
    },
  ] as const;
}
