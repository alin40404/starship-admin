/**
 * 管理员管理按钮权限，与 modules/System/app/Support/AdminPermissions.php 保持一致。
 */
export const ADMIN_PERMISSIONS = {
  BATCH_DELETE: 'system.admins.batch_delete',
  BATCH_DISABLE: 'system.admins.batch_disable',
  BATCH_ENABLE: 'system.admins.batch_enable',
  CREATE: 'system.admins.create',
  DELETE: 'system.admins.delete',
  QUERY: 'system.admins.query',
  STATUS: 'system.admins.status',
  UPDATE: 'system.admins.update',
} as const;

export type AdminPermissionCode =
  (typeof ADMIN_PERMISSIONS)[keyof typeof ADMIN_PERMISSIONS];
