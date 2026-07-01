/**
 * 角色管理按钮权限，与 modules/System/app/Support/RolePermissions.php 保持一致。
 */
export const ROLE_PERMISSIONS = {
  BATCH_DELETE: 'system.roles.batch_delete',
  BATCH_DISABLE: 'system.roles.batch_disable',
  BATCH_ENABLE: 'system.roles.batch_enable',
  CREATE: 'system.roles.create',
  DELETE: 'system.roles.delete',
  QUERY: 'system.roles.query',
  UPDATE: 'system.roles.update',
} as const;

export type RolePermissionCode =
  (typeof ROLE_PERMISSIONS)[keyof typeof ROLE_PERMISSIONS];
