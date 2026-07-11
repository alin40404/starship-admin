import { $te } from '@vben/locales';

import { $t } from '#/locales';

export const DICT_TYPE_CODE = {
  attachmentCategory: 'attachment_category',
  categoryType: 'category_type',
  sysStatus: 'sys_status',
} as const;

export function dictItemLocaleKey(typeCode: string, value: string): string {
  return `dict.items.${typeCode}.${value}.label`;
}

export function dictTypeLocaleKey(typeCode: string): string {
  return `dict.types.${typeCode}.name`;
}

export function dictItemLabel(
  typeCode: string,
  item: { label: string; value: string },
): string {
  const localeKey = dictItemLocaleKey(typeCode, item.value);
  return $te(localeKey) ? $t(localeKey) : item.label;
}

export function dictTypeName(type: { code: string; name: string }): string {
  const localeKey = dictTypeLocaleKey(type.code);
  return $te(localeKey) ? $t(localeKey) : type.name;
}

export function mapDictItemOptions<T extends { label: string; value: string }>(
  typeCode: string,
  items: T[],
): T[] {
  return items.map((item) => ({
    ...item,
    label: dictItemLabel(typeCode, item),
  }));
}

function resolveTypeCode(typeCode: string | (() => string)): string {
  return typeof typeCode === 'function' ? typeCode() : typeCode;
}

export function resolveDictItemLabel(
  typeCode: string | (() => string),
  item: { label: string; value: string },
): string {
  return dictItemLabel(resolveTypeCode(typeCode), item);
}
