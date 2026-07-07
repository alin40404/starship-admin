export const MODULE_PERMISSIONS = {
  MANAGE: 'system.modules',
} as const;

export type ModulePermissionCode =
  (typeof MODULE_PERMISSIONS)[keyof typeof MODULE_PERMISSIONS];

export const MODULE_STATUS_TAG_OPTIONS = [
  { color: 'error', label: '已禁用', value: false },
  { color: 'success', label: '已启用', value: true },
] as const;
