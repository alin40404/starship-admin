import { isAdminDevelopmentEnvironment } from '#/utils/admin-runtime-config';

export interface DevOnlyModuleLike {
  dev_only?: boolean;
}

export function isDevOnlyModuleUnavailable(
  module: DevOnlyModuleLike | null | undefined,
): boolean {
  return module?.dev_only === true && !isAdminDevelopmentEnvironment();
}

export function devOnlyModuleTagColor(
  module: DevOnlyModuleLike | null | undefined,
): 'default' | 'warning' {
  return isDevOnlyModuleUnavailable(module) ? 'warning' : 'default';
}
