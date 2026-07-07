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

export type DictAccessChecker = {
  can: (code: string) => boolean;
};

export function useDictTypeGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '字典名称' },
      fieldName: 'name',
      label: '名称',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '字典编码' },
      fieldName: 'code',
      label: '编码',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [
          { label: '启用', value: 1 },
          { label: '禁用', value: 0 },
        ],
        placeholder: '状态',
      },
      fieldName: 'status',
      label: '状态',
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
      placeholder: '搜索名称/编码',
    },
  });
}

export function useDictTypeColumns(
  onActionClick: OnActionClickFn<DictTypeRow>,
  access: DictAccessChecker,
): CrudGridColumn<DictTypeRow>[] {
  return [
    { field: 'id', sortable: true, title: 'ID', width: 72 },
    { align: 'left', field: 'name', minWidth: 140, sortable: true, title: '名称' },
    { align: 'left', field: 'code', minWidth: 140, sortable: true, title: '编码' },
    {
      field: 'items_count',
      formatter: ({ cellValue }: { cellValue: DictTypeRow['items_count'] }) =>
        cellValue === undefined || cellValue === null
          ? TABLE_CELL_EMPTY
          : String(cellValue),
      title: '字典项数',
      width: 100,
    },
    {
      align: 'center',
      cellRender: { name: 'CellTag' },
      field: 'status',
      mobile: {
        format: (row) => (row.status === 1 ? '启用' : '禁用'),
      },
      sortable: true,
      title: '状态',
      width: 100,
    },
    {
      field: 'remark',
      formatter: ({ cellValue }: { cellValue: DictTypeRow['remark'] }) =>
        cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 160,
      title: '备注',
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
      title: '更新时间',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: '字典',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'items',
            show: () => access.can(DICT_PERMISSIONS.QUERY),
            text: '字典项',
            type: 'link',
          },
          {
            code: 'edit',
            show: () => access.can(DICT_PERMISSIONS.UPDATE),
            text: '编辑',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(DICT_PERMISSIONS.DELETE),
            text: '删除',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
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
      label: '名称',
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', disabled: isEdit },
      fieldName: 'code',
      label: '编码',
      rules: 'required',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        optionType: 'button',
        options: [
          { label: '启用', value: 1 },
          { label: '禁用', value: 0 },
        ],
      },
      defaultValue: 1,
      fieldName: 'status',
      formItemClass: 'col-span-full w-full',
      label: '状态',
    },
    {
      component: 'Textarea',
      componentProps: {
        class: 'w-full',
        rows: 2,
      },
      fieldName: 'remark',
      formItemClass: 'col-span-full w-full items-start',
      label: '备注',
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
    { align: 'left', field: 'label', minWidth: 120, title: '标签' },
    { align: 'left', field: 'value', minWidth: 120, title: '值' },
    { field: 'sort', title: '排序', width: 80 },
    {
      align: 'center',
      cellRender: { name: 'CellTag' },
      field: 'status',
      mobile: {
        format: (row) => (row.status === 1 ? '启用' : '禁用'),
      },
      title: '状态',
      width: 100,
    },
    {
      field: 'remark',
      formatter: ({ cellValue }: { cellValue: DictItemRow['remark'] }) =>
        cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: '备注',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'label',
          nameTitle: '字典项',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(DICT_PERMISSIONS.UPDATE),
            text: '编辑',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(DICT_PERMISSIONS.DELETE),
            text: '删除',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
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
      label: '标签',
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', disabled: isEdit },
      fieldName: 'value',
      label: '值',
      rules: 'required',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: '排序',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        optionType: 'button',
        options: [
          { label: '启用', value: 1 },
          { label: '禁用', value: 0 },
        ],
      },
      defaultValue: 1,
      fieldName: 'status',
      label: '状态',
    },
    {
      component: 'Textarea',
      componentProps: { class: 'w-full', rows: 2 },
      fieldName: 'remark',
      formItemClass: 'col-span-full w-full items-start',
      label: '备注',
    },
  ];
}
