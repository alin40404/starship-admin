/**
 * 区域管理按钮权限，与 modules/System/app/Support/AreaPermissions.php 保持一致。
 */
export const REGION_PERMISSIONS = {
  /** 菜单页访问（与菜单 permission 一致） */
  MANAGE: 'system.regions',
  APPEND: 'system.regions.append',
  BATCH_DELETE: 'system.regions.batch_delete',
  CREATE: 'system.regions.create',
  DELETE: 'system.regions.delete',
  QUERY: 'system.regions.query',
  UPDATE: 'system.regions.update',
} as const;

export type RegionPermissionCode =
  (typeof REGION_PERMISSIONS)[keyof typeof REGION_PERMISSIONS];
