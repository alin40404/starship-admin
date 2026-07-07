import type { OnActionClickFn } from '#/adapter/vxe-table';

import type {
  MobileCrudListConfig,
  MobileCrudListItemConfig,
  MobileListField,
  MobileListOperation,
} from './types';

import { $t } from '@vben/locales';
import { formatDate, formatDateTime, get, isFunction } from '@vben/utils';

import { TABLE_CELL_EMPTY } from '#/constants/table';

const VXE_STRING_FORMATTERS: Record<string, (value: unknown) => string> = {
  formatDate: (value) => {
    const formatted = formatDate(value as Parameters<typeof formatDate>[0]);
    return formatted ?? TABLE_CELL_EMPTY;
  },
  formatDateTime: (value) => {
    const formatted = formatDateTime(value as Parameters<typeof formatDateTime>[0]);
    return formatted ?? TABLE_CELL_EMPTY;
  },
};

/** 扩展 vxe 列：移动端卡片语义（与 PC columns 同处维护） */
export interface CrudColumnMobileMeta<T = Record<string, any>> {
  /** 是否出现在卡片字段，默认随 PC visible；设为 true 可强制展示 PC 隐藏列 */
  card?: boolean;
  /** 移动端取值覆盖（优先于 formatter，用于 Switch 等交互列转文字） */
  format?: (row: T) => string;
}

export type CrudGridColumn<T = Record<string, any>> = {
  [key: string]: any;
  mobile?: CrudColumnMobileMeta<T>;
};

function isCheckboxColumn(column: CrudGridColumn) {
  return column.type === 'checkbox';
}

function columnsHasCheckbox<T>(columns: CrudGridColumn<T>[]) {
  return columns.some((column) => isCheckboxColumn(column));
}

function isOperationColumn(column: CrudGridColumn) {
  return column.cellRender?.name === 'CellOperation';
}

function isMobileCardFieldColumn<T>(column: CrudGridColumn<T>) {
  if (!column.field || isCheckboxColumn(column) || isOperationColumn(column)) {
    return false;
  }
  const mobile = column.mobile;
  if (column.visible === false && mobile?.card !== true) {
    return false;
  }
  return true;
}

function formatColumnCellValue<T>(column: CrudGridColumn<T>, row: T): string {
  if (column.mobile?.format) {
    return column.mobile.format(row);
  }
  if (column.cellRender?.name === 'CellImage') {
    const getSrc = column.cellRender?.attrs?.getSrc;
    if (isFunction(getSrc)) {
      const src = getSrc(row, column);
      return src ? String(src) : TABLE_CELL_EMPTY;
    }
  }
  if (column.cellRender?.name === 'CellSwitch' && column.field) {
    const value = get(row, column.field);
    if (value === 1 || value === true) {
      return '启用';
    }
    if (value === 0 || value === false) {
      return '禁用';
    }
    return TABLE_CELL_EMPTY;
  }
  if (column.formatter) {
    const cellValue = column.field ? get(row, column.field) : undefined;
    if (typeof column.formatter === 'string') {
      const stringFormatter = VXE_STRING_FORMATTERS[column.formatter];
      if (stringFormatter) {
        return stringFormatter(cellValue);
      }
    } else if (isFunction(column.formatter)) {
      const formatted = column.formatter({ row, cellValue });
      if (formatted === null || formatted === undefined || formatted === '') {
        return TABLE_CELL_EMPTY;
      }
      return String(formatted);
    }
  }
  if (!column.field) {
    return TABLE_CELL_EMPTY;
  }
  const value = get(row, column.field);
  if (value === null || value === undefined || value === '') {
    return TABLE_CELL_EMPTY;
  }
  return String(value);
}

export function buildMobileFieldsFromColumns<T>(
  columns: CrudGridColumn<T>[],
): MobileListField<T>[] {
  return columns
    .filter((column) => isMobileCardFieldColumn(column))
    .map((column) => {
      const isImage = column.cellRender?.name === 'CellImage';
      const isSwitch = column.cellRender?.name === 'CellSwitch';

      return {
        label: column.title ?? column.field ?? '',
        render: isImage ? ('image' as const) : isSwitch ? ('switch' as const) : undefined,
        show: column.mobile?.card === true && column.visible === false
          ? () => true
          : undefined,
        switch:
          isSwitch && column.field
            ? {
                beforeChange: column.cellRender?.attrs?.beforeChange,
                field: column.field,
                getSwitchProps: column.cellRender?.attrs?.getSwitchProps,
                props: column.cellRender?.props,
              }
            : undefined,
        value: (row: T) => formatColumnCellValue(column, row),
      };
    });
}

function findOperationColumn<T>(columns: CrudGridColumn<T>[]) {
  return columns.find((column) => isOperationColumn(column));
}

export function buildMobileOperationsFromColumns<T>(
  columns: CrudGridColumn<T>[],
): MobileListOperation<T>[] {
  const operationColumn = findOperationColumn(columns);
  if (!operationColumn) {
    return [];
  }

  const attrs = operationColumn.cellRender?.attrs ?? {};
  const options = operationColumn.cellRender?.options ?? [];

  return options.map((option) => {
    const operation: MobileListOperation<T> = {
      code: option.code,
      danger: option.danger,
      show: option.show,
      text: option.text ?? option.code,
      type: option.type ?? 'link',
    };

    if (option.mobile?.confirm) {
      operation.confirm = option.mobile.confirm;
    } else if (option.code === 'delete') {
      const nameTitle = attrs.nameTitle ?? '';
      const nameField = attrs.nameField ?? 'name';
      operation.confirm = {
        content: (row: T) =>
          $t('ui.actionMessage.deleteConfirm', [get(row, nameField)]),
        title: $t('ui.actionTitle.delete', [nameTitle]),
      };
    }

    return operation;
  });
}

export function extractOperationClickFromColumns<T>(
  columns: CrudGridColumn<T>[],
): OnActionClickFn<T> | undefined {
  const operationColumn = findOperationColumn(columns);
  const onClick = operationColumn?.cellRender?.attrs?.onClick;
  return isFunction(onClick) ? onClick : undefined;
}

export function buildMobileItemFromColumns<T>(
  columns: CrudGridColumn<T>[],
  overrides?: Partial<MobileCrudListItemConfig<T>>,
): MobileCrudListItemConfig<T> {
  return {
    fields: overrides?.fields ?? buildMobileFieldsFromColumns(columns),
    ...overrides,
  };
}

export interface BuildMobileCrudFromColumnsParams<
  T extends Record<string, any>,
> {
  columns: CrudGridColumn<T>[];
  defaultPageSize?: number;
  defaultSort?: MobileCrudListConfig<T>['defaultSort'];
  item?: Partial<MobileCrudListItemConfig<T>>;
  onOperationClick?: OnActionClickFn<T>;
  operations?: MobileListOperation<T>[];
  query: MobileCrudListConfig<T>['query'];
  rowKey: MobileCrudListConfig<T>['rowKey'];
  search?: MobileCrudListConfig<T>['search'];
  selection?: MobileCrudListConfig<T>['selection'];
}

export function buildMobileCrudFromColumns<T extends Record<string, any>>(
  params: BuildMobileCrudFromColumnsParams<T>,
): MobileCrudListConfig<T> {
  const {
    columns,
    defaultPageSize,
    defaultSort,
    item: itemOverrides,
    onOperationClick,
    operations,
    query,
    rowKey,
    search,
    selection,
  } = params;

  return {
    defaultPageSize,
    defaultSort,
    item: buildMobileItemFromColumns(columns, itemOverrides),
    onOperationClick:
      onOperationClick ?? extractOperationClickFromColumns(columns),
    operations: operations ?? buildMobileOperationsFromColumns(columns),
    query,
    rowKey,
    search,
    selection:
      selection !== undefined
        ? selection
        : columnsHasCheckbox(columns)
          ? { enabled: true }
          : undefined,
  };
}
