/**
 * 岗位管理按钮权限，与 modules/System/app/Support/PostPermissions.php 保持一致。
 */
export const POST_PERMISSIONS = {
  BATCH_DELETE: 'system.posts.batch_delete',
  BATCH_STATUS: 'system.posts.batch_status',
  CREATE: 'system.posts.create',
  DELETE: 'system.posts.delete',
  QUERY: 'system.posts.query',
  UPDATE: 'system.posts.update',
} as const;

export type PostPermissionCode =
  (typeof POST_PERMISSIONS)[keyof typeof POST_PERMISSIONS];
