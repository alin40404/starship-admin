/**
 * 用户组管理按钮权限，与 modules/System/app/Support/UserGroupPermissions.php 保持一致。
 */
export const USER_GROUP_PERMISSIONS = {
  BATCH_DELETE: 'system.user_groups.batch_delete',
  CREATE: 'system.user_groups.create',
  DELETE: 'system.user_groups.delete',
  QUERY: 'system.user_groups.query',
  UPDATE: 'system.user_groups.update',
} as const;

export type UserGroupPermissionCode =
  (typeof USER_GROUP_PERMISSIONS)[keyof typeof USER_GROUP_PERMISSIONS];
