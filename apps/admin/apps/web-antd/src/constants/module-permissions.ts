/**
 * 模块管理权限，与 modules/System/app/Support/ModulePermissions.php 保持一致。
 */
import { $t } from '#/locales';

export const MODULE_PERMISSIONS = {
  /** 菜单页访问（与菜单 permission 一致） */
  MANAGE: 'system.modules',
  QUERY: 'system.modules.query',
  UPDATE: 'system.modules.update',
} as const;

export type ModulePermissionCode =
  (typeof MODULE_PERMISSIONS)[keyof typeof MODULE_PERMISSIONS];

export function getModuleStatusTagOptions() {
  return [
    { color: 'error', label: $t('dev.shared.disable'), value: false },
    { color: 'success', label: $t('dev.shared.enable'), value: true },
  ] as const;
}
