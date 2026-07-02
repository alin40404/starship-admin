import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { AreaTreeNode } from '#/api/system/region';
import { TABLE_CELL_EMPTY } from '#/constants/table';

export const AREA_LEVEL_OPTIONS = [
  { label: '省', value: 1 },
  { label: '市', value: 2 },
  { label: '区/县', value: 3 },
] as const;

export const AREA_LEVEL_TAG_OPTIONS = [
  { color: 'processing', label: '省', value: 1 },
  { color: 'success', label: '市', value: 2 },
  { color: 'default', label: '区/县', value: 3 },
];

export function formatAreaLevel(level: AreaTreeNode['level']) {
  if (level === 1) return '省';
  if (level === 2) return '市';
  if (level === 3) return '区/县';
  return TABLE_CELL_EMPTY;
}

export function useAreaGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '区域名称' },
      fieldName: 'name',
      label: '名称',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...AREA_LEVEL_OPTIONS],
        placeholder: '层级',
      },
      fieldName: 'level',
      label: '层级',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '拼音' },
      fieldName: 'pinyin',
      label: '拼音',
    },
  ];
}

export function useAreaColumns(
  onActionClick: OnActionClickFn<AreaTreeNode>,
  canManage: () => boolean,
): VxeTableGridColumns<AreaTreeNode> {
  return [
    {
      align: 'left',
      field: 'name',
      minWidth: 180,
      title: '名称',
      treeNode: true,
    },
    {
      align: 'left',
      field: 'shortname',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 100,
      title: '简称',
    },
    {
      align: 'center',
      cellRender: { name: 'CellTag', options: AREA_LEVEL_TAG_OPTIONS },
      field: 'level',
      title: '层级',
      width: 88,
    },
    {
      align: 'left',
      field: 'mergename',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 220,
      title: '全称',
    },
    {
      align: 'left',
      field: 'pinyin',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: '拼音',
    },
    {
      field: 'code',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 90,
      title: '区号',
    },
    {
      field: 'zip',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 90,
      title: '邮编',
    },
    {
      align: 'center',
      field: 'first',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      title: '首字母',
      width: 80,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: '区域',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'append',
            show: () => canManage(),
            text: '新增下级',
            type: 'link',
          },
          {
            code: 'edit',
            show: () => canManage(),
            text: '编辑',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => canManage(),
            text: '删除',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 220,
    },
  ];
}

export function buildAreaFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      controlClass: 'w-full min-w-0',
      fieldName: 'pid',
      formItemClass: 'col-span-full w-full items-start',
      label: '上级区域',
    },
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
      fieldName: 'shortname',
      label: '简称',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'mergename',
      label: '全称',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...AREA_LEVEL_OPTIONS],
        placeholder: '留空则根据上级自动推断',
      },
      fieldName: 'level',
      label: '层级',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        disabled: true,
        placeholder: '根据简称自动生成',
        readonly: true,
      },
      fieldName: 'pinyin',
      label: '拼音',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'code',
      label: '长途区号',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'zip',
      label: '邮编',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        disabled: true,
        placeholder: '根据拼音自动生成',
        readonly: true,
      },
      fieldName: 'first',
      label: '首字母',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'lng',
      label: '经度',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full' },
      fieldName: 'lat',
      label: '纬度',
    },
  ];
}
