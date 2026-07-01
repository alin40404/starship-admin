/**
 * 菜单管理按钮权限，与 modules/System/app/Support/MenuPermissions.php 保持一致。
 */
export const MENU_PERMISSIONS = {
  APPEND: 'system.menus.append',
  BATCH_DELETE: 'system.menus.batch_delete',
  BATCH_DISABLE: 'system.menus.batch_disable',
  BATCH_ENABLE: 'system.menus.batch_enable',
  CREATE: 'system.menus.create',
  DELETE: 'system.menus.delete',
  QUERY: 'system.menus.query',
  SORT: 'system.menus.sort',
  STATUS: 'system.menus.status',
  UPDATE: 'system.menus.update',
} as const;

export type MenuPermissionCode =
  (typeof MENU_PERMISSIONS)[keyof typeof MENU_PERMISSIONS];
