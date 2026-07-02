import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { PostRow } from '#/api/system/post';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { POST_PERMISSIONS } from '#/constants/post-permissions';

export type PostAccessChecker = {
  can: (code: string) => boolean;
  canAny: (codes: string[]) => boolean;
};

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '岗位名称' },
      fieldName: 'name',
      label: '名称',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '编码' },
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

export function useColumns(
  onActionClick: OnActionClickFn<PostRow>,
  onStatusBeforeChange: (newVal: number, row: PostRow) => Promise<boolean>,
  access: PostAccessChecker,
): VxeTableGridColumns {
  const canUpdate = access.can(POST_PERMISSIONS.UPDATE);

  return [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    { field: 'id', sortable: true, title: 'ID', width: 72 },
    { align: 'left', field: 'name', minWidth: 140, sortable: true, title: '名称' },
    { align: 'left', field: 'code', minWidth: 140, sortable: true, title: '编码' },
    { field: 'sort', sortable: true, title: '排序', width: 80 },
    {
      field: 'status',
      sortable: true,
      title: '状态',
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: PostRow) {
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: () => ({
            disabled: !canUpdate,
          }),
        },
        name: 'CellSwitch',
      },
    },
    {
      field: 'remark',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 160,
      title: '备注',
    },
    {
      field: 'updated_at',
      minWidth: 170,
      sortable: true,
      title: '更新时间',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: '岗位',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(POST_PERMISSIONS.UPDATE),
            text: '编辑',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(POST_PERMISSIONS.DELETE),
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

export function buildPostFormSchema(readonly = false): VbenFormSchema[] {
  const disabled = readonly;
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'name',
      label: '名称',
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', disabled },
      fieldName: 'code',
      label: '编码',
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', disabled, min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: '排序',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        buttonStyle: 'solid',
        class: 'w-full',
        disabled,
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
      componentProps: { class: 'w-full', disabled, rows: 2 },
      fieldName: 'remark',
      formItemClass: 'col-span-full w-full items-start',
      label: '备注',
    },
  ];
}
