import type { FormSchema } from './types';

export interface CrudSearchViews<T extends FormSchema = FormSchema> {
  keywordFields: string[];
  mobileDrawer: T[];
  pcAll: T[];
  pcInline: T[];
  pcPanel: T[];
}

function isVisibleSchemaItem(item: FormSchema) {
  return !item.hide;
}

/**
 * 将一份搜索 schema 派生为 PC 工具栏 / PC 筛选面板 / 移动端 Drawer 视图。
 */
export function resolveCrudSearchViews<T extends FormSchema = FormSchema>(
  schema: T[] | undefined | null,
): CrudSearchViews<T> {
  const visible = (schema ?? []).filter(isVisibleSchemaItem) as T[];

  const pcAll = visible.filter((item) => !item.hideOnPc);
  const pcInline = pcAll.filter((item) => !item.inFilterPanel);
  const pcPanel = pcAll.filter((item) => !!item.inFilterPanel);
  const mobileDrawer = visible.filter((item) => !item.hideOnMobile);
  const keywordFields = visible
    .filter((item) => !!item.inKeywordSearch)
    .map((item) => item.fieldName);

  return {
    keywordFields,
    mobileDrawer,
    pcAll,
    pcInline,
    pcPanel,
  };
}

export function isSearchFieldEmpty(value: unknown): boolean {
  if (value === undefined || value === null) {
    return true;
  }
  if (typeof value === 'string') {
    return value.trim() === '';
  }
  if (Array.isArray(value)) {
    return value.length === 0;
  }
  return false;
}

export function countFilledSearchFields(
  values: Record<string, any>,
  fields: Array<{ fieldName: string }>,
): number {
  return fields.reduce((count, field) => {
    return count + (isSearchFieldEmpty(values[field.fieldName]) ? 0 : 1);
  }, 0);
}
