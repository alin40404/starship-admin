/**
 * 字典管理按钮权限，与 modules/System/app/Support/DictPermissions.php 保持一致。
 */
export const DICT_PERMISSIONS = {
  /** 菜单页访问（与菜单 permission 一致） */
  MANAGE: 'system.dicts',
  CREATE: 'system.dicts.create',
  DELETE: 'system.dicts.delete',
  QUERY: 'system.dicts.query',
  UPDATE: 'system.dicts.update',
} as const;

export type DictPermissionCode =
  (typeof DICT_PERMISSIONS)[keyof typeof DICT_PERMISSIONS];
