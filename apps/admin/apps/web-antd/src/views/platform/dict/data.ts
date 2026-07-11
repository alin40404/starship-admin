import type { VbenFormSchema } from '#/adapter/form';
import type {
  CrudGridColumn,
  MobileCrudListConfig,
} from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { DictItemRow, DictTypeRow } from '#/api/system/dict';
import { formatDateTime } from '@vben/utils';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { DICT_PERMISSIONS } from '#/constants/dict-permissions';
import { $t } from '#/locales';

export type DictAccessChecker = {
  can: (code: string) => boolean;
};

function getStatusOptions() {
  return [
    { label: $t('system.shared.status.enable'), value: 1 },
    { label: $t('system.shared.status.disable'), value: 0 },
  ];
}

export function useDictTypeGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('platform.dict.searchName'),
      },
      fieldName: 'name',
      label: $t('platform.dict.name'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('platform.dict.searchCode'),
      },
      fieldName: 'code',
      label: $t('platform.dict.code'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: getStatusOptions(),
        placeholder: $t('platform.dict.status'),
      },
      fieldName: 'status',
      label: $t('platform.dict.status'),
    },
  ];
}

export function buildDictTypeMobileCrudConfig(params: {
  columns: CrudGridColumn<DictTypeRow>[];
  query: MobileCrudListConfig<DictTypeRow>['query'];
}): MobileCrudListConfig<DictTypeRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 20,
    query: params.query,
    rowKey: 'id',
    search: {
      filterSchema: useDictTypeGridFormSchema(),
      keywordFields: ['name', 'code'],
      placeholder: $t('platform.dict.searchKeyword'),
    },
  });
}

export function useDictTypeColumns(
  onActionClick: OnActionClickFn<DictTypeRow>,
  access: DictAccessChecker,
): CrudGridColumn<DictTypeRow>[] {
  return [
    { field: 'id', sortable: true, title: 'ID', width: 72 },
    {
      align: 'left',
      field: 'name',
      minWidth: 140,
      sortable: true,
      title: $t('platform.dict.name'),
    },
    {
      align: 'left',
      field: 'code',
      minWidth: 140,
      sortable: true,
      title: $t('platform.dict.code'),
    },
    {
      field: 'items_count',
      formatter: ({ cellValue }: { cellValue: DictTypeRow['items_count'] }) =>
        cellValue === undefined || cellValue === null
          ? TABLE_CELL_EMPTY
          : String(cellValue),
      title: $t('platform.dict.itemsCount'),
      width: 100,
    },
    {
      align: 'center',
      cellRender: { name: 'CellTag' },
      field: 'status',
      mobile: {
        format: (row) =>
          row.status === 1
            ? $t('system.shared.status.enable')
            : $t('system.shared.status.disable'),
      },
      sortable: true,
      title: $t('platform.dict.status'),
      width: 100,
    },
    {
      field: 'remark',
      formatter: ({ cellValue }: { cellValue: DictTypeRow['remark'] }) =>
        cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 160,
      title: $t('platform.dict.remark'),
    },
    {
      field: 'updated_at',
      formatter: 'formatDateTime',
      minWidth: 170,
      mobile: {
        format: (row) =>
          row.updated_at ? formatDateTime(row.updated_at) : TABLE_CELL_EMPTY,
      },
      sortable: true,
      title: $t('platform.dict.updatedAt'),
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: $t('platform.dict.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'items',
            show: () => access.can(DICT_PERMISSIONS.QUERY),
            text: $t('platform.dict.dictItems'),
            type: 'link',
          },
          {
            code: 'edit',
            show: () => access.can(DICT_PERMISSIONS.UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(DICT_PERMISSIONS.DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 200,
    },
  ] as CrudGridColumn<DictTypeRow>[];
}

export function buildDictTypeFormSchema(isEdit = false): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'name',
      label: $t('platform.dict.name'),
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', disabled: isEdit },
      fieldName: 'code',
      label: $t('platform.dict.code'),
      rules: 'required',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        optionType: 'button',
        options: getStatusOptions(),
      },
      defaultValue: 1,
      fieldName: 'status',
      formItemClass: 'col-span-full w-full',
      label: $t('platform.dict.status'),
    },
    {
      component: 'Textarea',
      componentProps: {
        class: 'w-full',
        rows: 2,
      },
      fieldName: 'remark',
      formItemClass: 'col-span-full w-full items-start',
      label: $t('platform.dict.remark'),
    },
  ];
}

export function buildDictItemMobileCrudConfig(params: {
  columns: CrudGridColumn<DictItemRow>[];
  query: MobileCrudListConfig<DictItemRow>['query'];
}): MobileCrudListConfig<DictItemRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 20,
    query: params.query,
    rowKey: 'id',
  });
}

export function useDictItemColumns(
  onActionClick: OnActionClickFn<DictItemRow>,
  access: DictAccessChecker,
): CrudGridColumn<DictItemRow>[] {
  return [
    { field: 'id', title: 'ID', width: 72 },
    {
      align: 'left',
      field: 'label',
      minWidth: 120,
      title: $t('platform.dict.label'),
    },
    {
      align: 'left',
      field: 'value',
      minWidth: 120,
      title: $t('platform.dict.value'),
    },
    { field: 'sort', title: $t('platform.dict.sort'), width: 80 },
    {
      align: 'center',
      cellRender: { name: 'CellTag' },
      field: 'status',
      mobile: {
        format: (row) =>
          row.status === 1
            ? $t('system.shared.status.enable')
            : $t('system.shared.status.disable'),
      },
      title: $t('platform.dict.status'),
      width: 100,
    },
    {
      field: 'remark',
      formatter: ({ cellValue }: { cellValue: DictItemRow['remark'] }) =>
        cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: $t('platform.dict.remark'),
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'label',
          nameTitle: $t('platform.dict.itemEntity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(DICT_PERMISSIONS.UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(DICT_PERMISSIONS.DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 140,
    },
  ] as CrudGridColumn<DictItemRow>[];
}

export function buildDictItemFormSchema(isEdit = false): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'label',
      label: $t('platform.dict.label'),
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', disabled: isEdit },
      fieldName: 'value',
      label: $t('platform.dict.value'),
      rules: 'required',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: $t('platform.dict.sort'),
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        optionType: 'button',
        options: getStatusOptions(),
      },
      defaultValue: 1,
      fieldName: 'status',
      label: $t('platform.dict.status'),
    },
    {
      component: 'Textarea',
      componentProps: { class: 'w-full', rows: 2 },
      fieldName: 'remark',
      formItemClass: 'col-span-full w-full items-start',
      label: $t('platform.dict.remark'),
    },
  ];
}
