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

export function getPluginStatusTagOptions() {
  return [
    {
      color: 'default',
      label: $t('dev.plugins.statusDiscovered'),
      value: PLUGIN_STATUS.DISCOVERED,
    },
    {
      color: 'processing',
      label: $t('dev.plugins.statusInstalled'),
      value: PLUGIN_STATUS.INSTALLED,
    },
    {
      color: 'success',
      label: $t('dev.plugins.statusEnabled'),
      value: PLUGIN_STATUS.ENABLED,
    },
    {
      color: 'warning',
      label: $t('dev.plugins.statusDisabled'),
      value: PLUGIN_STATUS.DISABLED,
    },
  ] as const;
}
