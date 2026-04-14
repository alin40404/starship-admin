import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { RoleRow } from '#/api/system/role';

/** 与后端 data_scope 1–5 校验一致（可按业务再调文案） */
export const DATA_SCOPE_OPTIONS = [
  { label: '全部', value: 1 },
  { label: '自定义', value: 2 },
  { label: '本部门', value: 3 },
  { label: '本部门及下级', value: 4 },
  { label: '仅本人', value: 5 },
];

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '角色名称',
      },
      fieldName: 'name',
      label: '名称',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: '编码',
      },
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
  onActionClick: OnActionClickFn<RoleRow>,
  onStatusBeforeChange: (newVal: number, row: RoleRow) => Promise<boolean>,
): VxeTableGridColumns {
  return [
    { field: 'id', sortable: true, title: 'ID', width: 72 },
    { field: 'name', minWidth: 140, sortable: true, title: '名称' },
    { field: 'code', minWidth: 140, sortable: true, title: '编码' },
    {
      field: 'data_scope',
      formatter: ({ cellValue }) =>
        DATA_SCOPE_OPTIONS.find((o) => o.value === cellValue)?.label ??
        String(cellValue ?? '—'),
      minWidth: 120,
      sortable: true,
      title: '数据范围',
    },
    { field: 'sort', sortable: true, title: '排序', width: 80 },
    {
      field: 'status',
      sortable: true,
      title: '状态',
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: RoleRow) {
            if (row.code === 'super_admin') {
              return false;
            }
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: (row: RoleRow) => ({
            disabled: row.code === 'super_admin',
          }),
        },
        name: 'CellSwitch',
      },
    },
    {
      field: 'remark',
      minWidth: 160,
      sortable: false,
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
          nameTitle: '角色',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'view',
            show: (row: RoleRow) => row.code === 'super_admin',
            text: '查看',
            type: 'link',
          },
          {
            code: 'edit',
            show: (row: RoleRow) => row.code !== 'super_admin',
            text: '编辑',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: (row: RoleRow) => row.code !== 'super_admin',
            text: '删除',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 180,
    },
  ];
}

export function buildRoleFormSchema(params: {
  isEdit: boolean;
  lockBuiltin: boolean;
  /** 查看模式：全部只读（如超级管理员） */
  readonly?: boolean;
}): VbenFormSchema[] {
  const { isEdit, lockBuiltin, readonly = false } = params;
  const disabled = readonly || lockBuiltin;
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        disabled,
      },
      fieldName: 'name',
      label: '名称',
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        disabled: disabled || isEdit,
      },
      fieldName: 'code',
      label: '编码',
      rules: readonly ? undefined : 'required',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: !readonly,
        class: 'w-full',
        disabled,
        options: DATA_SCOPE_OPTIONS,
        placeholder: '数据范围',
      },
      defaultValue: 1,
      fieldName: 'data_scope',
      label: '数据范围',
      rules: readonly ? undefined : 'selectRequired',
    },
    {
      component: 'InputNumber',
      componentProps: {
        class: 'w-full',
        disabled,
        min: 0,
      },
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
      componentProps: {
        class: 'w-full',
        disabled,
        rows: 3,
      },
      fieldName: 'remark',
      formItemClass: 'sm:col-span-2',
      label: '备注',
    },
    /** 菜单授权：由表单插槽渲染 a-tree，值参与 getValues */
    {
      component: 'Input',
      defaultValue: [],
      fieldName: 'menu_ids',
      formItemClass: 'sm:col-span-2 items-start',
      label: '菜单权限',
    },
  ];
}
