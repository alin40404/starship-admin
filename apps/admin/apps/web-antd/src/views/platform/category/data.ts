import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { CategoryRow } from '#/api/system/category';
import { CATEGORY_PERMISSIONS } from '#/constants/category-permissions';
import { TABLE_CELL_EMPTY } from '#/constants/table';

export type CategoryAccessChecker = {
  can: (code: string) => boolean;
};

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '分类名称' },
      fieldName: 'label',
      label: '分类名称',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '分类标识' },
      fieldName: 'value',
      label: '分类标识',
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

export function buildFormSchema(isEdit = false): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '如：图片、文档',
      },
      fieldName: 'label',
      label: '分类名称',
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        disabled: isEdit,
        placeholder: '小写字母开头，如 image、document',
      },
      fieldName: 'value',
      help: isEdit
        ? '分类标识创建后不可修改（已有附件时）'
        : '用于附件存储与筛选，创建后请谨慎修改',
      label: '分类标识',
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

export function useColumns(
  onActionClick: OnActionClickFn<CategoryRow>,
  access: CategoryAccessChecker,
): VxeTableGridColumns<CategoryRow> {
  return [
    { field: 'id', sortable: true, title: 'ID', width: 72 },
    {
      align: 'left',
      field: 'label',
      minWidth: 140,
      sortable: true,
      title: '分类名称',
    },
    {
      align: 'left',
      field: 'value',
      minWidth: 140,
      sortable: true,
      title: '分类标识',
    },
    { field: 'sort', sortable: true, title: '排序', width: 88 },
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
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
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
          nameField: 'label',
          nameTitle: '分类',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(CATEGORY_PERMISSIONS.UPDATE),
            text: '编辑',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(CATEGORY_PERMISSIONS.DELETE),
            text: '删除',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 140,
    },
  ];
}
