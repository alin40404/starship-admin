import type { VbenFormProps, VbenFormSchema } from '#/adapter/form';

export interface MobileListQueryParams {
  page: number;
  pageSize: number;
  keyword?: string;
  filters?: Record<string, any>;
  sortField?: string;
  sortOrder?: 'asc' | 'desc';
}

export interface MobileListQueryResult<T> {
  data: T[];
  total: number;
}

export interface MobileListTag {
  color?: string;
  label: string;
}

export interface MobileListSwitchConfig<T> {
  beforeChange?: (
    newVal: unknown,
    row: T,
  ) => boolean | Promise<boolean | void>;
  checkedChildren?: string;
  checkedValue?: boolean | number | string;
  field: string;
  getSwitchProps?: (row: T) => Record<string, any>;
  props?: Record<string, any>;
  unCheckedChildren?: string;
  unCheckedValue?: boolean | number | string;
}

export interface MobileListField<T> {
  label: string;
  /** 与 PC 列 visible:false 对齐，默认不在卡片展示，但可在列设置中勾选 */
  hiddenByDefault?: boolean;
  /** 字段渲染方式 */
  render?: 'image' | 'switch';
  show?: (row: T) => boolean;
  switch?: MobileListSwitchConfig<T>;
  value: (row: T) => string;
}

export interface MobileListOperation<T> {
  code: string;
  confirm?: {
    content?: (row: T) => string;
    title?: string;
  };
  danger?: boolean;
  show?: ((row: T) => boolean) | boolean;
  text: string;
  /** 与 PC CellOperation 一致，如 link / primary / default */
  type?: 'dashed' | 'default' | 'link' | 'primary' | 'text';
}

export interface MobileCrudListItemConfig<T> {
  fields: MobileListField<T>[];
}

export interface MobileCrudListSearchConfig {
  fieldMappingTime?: VbenFormProps['fieldMappingTime'];
  filterSchema?: VbenFormSchema[];
  keywordFields?: string[];
  placeholder?: string;
  submitOnChange?: boolean;
}

export interface MobileCrudListSelectionConfig<T> {
  checkable?: (row: T) => boolean;
  enabled?: boolean;
}

export interface MobileCrudListConfig<T extends Record<string, any>> {
  defaultPageSize?: number;
  defaultSort?: { field: string; order: 'asc' | 'desc' };
  item: MobileCrudListItemConfig<T>;
  onOperationClick?: (params: { code: string; row: T }) => void;
  operations?: MobileListOperation<T>[];
  query: (params: MobileListQueryParams) => Promise<MobileListQueryResult<T>>;
  rowKey: ((row: T) => number | string) | keyof T;
  search?: MobileCrudListSearchConfig;
  selection?: MobileCrudListSelectionConfig<T>;
}

export interface MobileCrudListApi<T> {
  clearSelection: () => void;
  getSelectedRows: () => T[];
  reload: () => Promise<void>;
}

export type MobileCrudListProps<T extends Record<string, any>> = {
  config: MobileCrudListConfig<T>;
  selectedRows?: T[];
  'onUpdate:selectedRows'?: (rows: T[]) => void;
};
