export interface MenuI18nSource {
  name: string;
  title?: string | null;
  permission?: string | null;
  module?: { name: string } | null;
}

/** 菜单标题 i18n 候选 key：先 system 内置，再按模块 namespace 查找 */
export function menuI18nKeyCandidates(node: MenuI18nSource): string[] {
  const keys = [`system.menu.${node.name}`];

  const moduleSlug =
    node.module?.name?.trim().toLowerCase() ||
    node.permission?.trim().split('.')[0]?.toLowerCase() ||
    '';

  if (moduleSlug && moduleSlug !== 'system') {
    keys.push(`${moduleSlug}.menu.${node.name}`);
  }

  return keys;
}

export function resolveMenuI18nKey(
  node: MenuI18nSource,
  hasTranslation: (key: string) => boolean,
): string | null {
  for (const key of menuI18nKeyCandidates(node)) {
    if (hasTranslation(key)) {
      return key;
    }
  }

  return null;
}
