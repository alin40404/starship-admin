/**
 * 部门管理按钮权限，与 modules/System/app/Support/DepartmentPermissions.php 保持一致。
 */
export const DEPARTMENT_PERMISSIONS = {
  APPEND: 'system.departments.append',
  CREATE: 'system.departments.create',
  DELETE: 'system.departments.delete',
  QUERY: 'system.departments.query',
  STATUS: 'system.departments.status',
  UPDATE: 'system.departments.update',
} as const;

export type DepartmentPermissionCode =
  (typeof DEPARTMENT_PERMISSIONS)[keyof typeof DEPARTMENT_PERMISSIONS];
