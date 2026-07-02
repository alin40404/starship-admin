import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { DictItemRow, DictTypeRow } from '#/api/system/dict';
import { TABLE_CELL_EMPTY } from '#/constants/table';

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

export function useDictTypeColumns(
  onActionClick: OnActionClickFn<DictTypeRow>,
): VxeTableGridColumns<DictTypeRow> {
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
          { code: 'items', text: '字典项', type: 'link' },
          { code: 'edit', text: '编辑', type: 'link' },
          { code: 'delete', danger: true, text: '删除', type: 'link' },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 200,
    },
  ];
}

export function buildDictTypeFormSchema(): VbenFormSchema[] {
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
      componentProps: { class: 'w-full' },
      fieldName: 'code',
      label: '编码',
      rules: 'required',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
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
      componentProps: {
        class: 'w-full',
        rows: 2,
      },
      fieldName: 'remark',
      label: '备注',
    },
  ];
}

export function useDictItemColumns(
  onActionClick: OnActionClickFn<DictItemRow>,
): VxeTableGridColumns<DictItemRow> {
  return [
    { field: 'id', title: 'ID', width: 72 },
    { align: 'left', field: 'label', minWidth: 120, title: '标签' },
    { align: 'left', field: 'value', minWidth: 120, title: '值' },
    { field: 'sort', title: '排序', width: 80 },
    {
      align: 'center',
      cellRender: { name: 'CellTag' },
      field: 'status',
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
          { code: 'edit', text: '编辑', type: 'link' },
          { code: 'delete', danger: true, text: '删除', type: 'link' },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 140,
    },
  ];
}

export function buildDictItemFormSchema(): VbenFormSchema[] {
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
      componentProps: { class: 'w-full' },
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
