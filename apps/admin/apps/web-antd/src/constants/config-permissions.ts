/**
 * 系统配置按钮权限，与 modules/System/app/Support/ConfigPermissions.php 保持一致。
 */
export const CONFIG_PERMISSIONS = {
  /** 菜单页访问（与菜单 permission 一致） */
  MANAGE: 'system.configs',
  QUERY: 'system.configs.query',
  UPDATE: 'system.configs.update',
} as const;

export type ConfigPermissionCode =
  (typeof CONFIG_PERMISSIONS)[keyof typeof CONFIG_PERMISSIONS];
