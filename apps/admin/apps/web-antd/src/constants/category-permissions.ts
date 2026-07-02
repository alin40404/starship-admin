/**
 * 分类管理按钮权限，与 modules/System/app/Support/CategoryPermissions.php 保持一致。
 */
export const CATEGORY_PERMISSIONS = {
  APPEND: 'system.categories.append',
  BATCH_DELETE: 'system.categories.batch_delete',
  BATCH_STATUS: 'system.categories.batch_status',
  CREATE: 'system.categories.create',
  DELETE: 'system.categories.delete',
  QUERY: 'system.categories.query',
  SORT: 'system.categories.sort',
  STATUS: 'system.categories.status',
  UPDATE: 'system.categories.update',
} as const;

export type CategoryPermissionCode =
  (typeof CATEGORY_PERMISSIONS)[keyof typeof CATEGORY_PERMISSIONS];
