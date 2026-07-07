/**
 * 模块管理权限，与 modules/System/app/Support/ModulePermissions.php 保持一致。
 */
export const MODULE_PERMISSIONS = {
  /** 菜单页访问（与菜单 permission 一致） */
  MANAGE: 'system.modules',
  QUERY: 'system.modules.query',
  UPDATE: 'system.modules.update',
} as const;

export type ModulePermissionCode =
  (typeof MODULE_PERMISSIONS)[keyof typeof MODULE_PERMISSIONS];

export const MODULE_STATUS_TAG_OPTIONS = [
  { color: 'error', label: '已禁用', value: false },
  { color: 'success', label: '已启用', value: true },
] as const;
