/**
 * 插件管理权限，与 modules/System/app/Support/PluginPermissions.php 保持一致。
 */
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

export const PLUGIN_STATUS_TAG_OPTIONS = [
  { color: 'default', label: '未安装', value: PLUGIN_STATUS.DISCOVERED },
  { color: 'processing', label: '已安装', value: PLUGIN_STATUS.INSTALLED },
  { color: 'success', label: '已启用', value: PLUGIN_STATUS.ENABLED },
  { color: 'warning', label: '已禁用', value: PLUGIN_STATUS.DISABLED },
] as const;
