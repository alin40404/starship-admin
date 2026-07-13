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

export function devOnlyModuleTooltipKey(
  module: DevOnlyModuleLike | null | undefined,
): 'dev.shared.devOnlyTooltipDev' | 'dev.shared.devOnlyTooltipProd' {
  return isDevOnlyModuleUnavailable(module)
    ? 'dev.shared.devOnlyTooltipProd'
    : 'dev.shared.devOnlyTooltipDev';
}
