/** 分类管理权限，与 CategoryPermissions.php 保持一致 */
export const CATEGORY_PERMISSIONS = {
  CREATE: 'system.categories.create',
  DELETE: 'system.categories.delete',
  MANAGE: 'system.categories',
  QUERY: 'system.categories.query',
  UPDATE: 'system.categories.update',
} as const;
