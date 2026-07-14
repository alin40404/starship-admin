import type { MenuTreeNode } from '#/api/system/menu';

import { menuDisplayLabel } from '#/views/permission/menu/data';

export interface MenuParentTreeOption {
  children?: MenuParentTreeOption[];
  label: string;
  value: string;
}

/** 与 GeneratorNaming::defaultRouteGroup 默认父菜单一致 */
const MENU_PARENT_DEFAULTS: Record<string, string> = {
  system: '/org',
  monitor: '/monitor',
};

export function defaultMenuParentPath(module: string): string | undefined {
  return MENU_PARENT_DEFAULTS[module.trim().toLowerCase()];
}

export function mapMenuTreeForParentPathSelect(
  nodes: MenuTreeNode[],
): MenuParentTreeOption[] {
  return nodes
    .filter((node) => node.type !== 3 && node.path?.trim())
    .map((node) => ({
      value: node.path!.trim(),
      label: `${menuDisplayLabel(node)} (${node.path})`,
      children: node.children?.length
        ? mapMenuTreeForParentPathSelect(node.children)
        : undefined,
    }));
}

export function menuParentPathExists(
  options: MenuParentTreeOption[],
  path: string,
): boolean {
  for (const option of options) {
    if (option.value === path) {
      return true;
    }
    if (
      option.children?.length &&
      menuParentPathExists(option.children, path)
    ) {
      return true;
    }
  }
  return false;
}
