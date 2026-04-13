import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { RoleRow } from '#/api/system/role';

/** 与种子数据一致：内置超级管理员角色 */
export const SUPER_ADMIN_ROLE_CODE = 'super_admin';

export function isSuperAdminRole(row: RoleRow): boolean {
  return row.code === SUPER_ADMIN_ROLE_CODE;
}

const dataScopeOptions = [
  { label: '全部数据', value: 1 },
  { label: '本部门', value: 2 },
  { label: '本部门及下级', value: 3 },
  { label: '仅本人', value: 4 },
  { label: '自定义', value: 5 },
];

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      fieldName: 'name',
      label: '角色名称',
    },
    {
      component: 'Input',
      fieldName: 'code',
      label: '编码',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        options: [
          { label: '启用', value: 1 },
          { label: '禁用', value: 0 },
        ],
      },
      fieldName: 'status',
      label: '状态',
    },
  ];
}

export function buildRoleFormSchema(isEdit: boolean): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      fieldName: 'name',
      label: '名称',
      rules: 'required',
    },
    {
      component: 'Input',
      componentProps: {
        disabled: isEdit,
      },
      fieldName: 'code',
      label: '编码',
      rules: 'required',
    },
    {
      component: 'Select',
      componentProps: {
        options: dataScopeOptions,
      },
      defaultValue: 1,
      fieldName: 'data_scope',
      label: '数据范围',
    },
    {
      component: 'InputNumber',
      componentProps: { min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: '排序',
    },
    {
      component: 'RadioGroup',
      componentProps: {
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
      component: 'Input',
      componentProps: {
        rows: 2,
        type: 'textarea',
      },
      fieldName: 'remark',
      label: '备注',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<RoleRow>,
): VxeTableGridColumns {
  return [
    { field: 'id', title: 'ID', width: 70 },
    { field: 'name', minWidth: 140, title: '名称' },
    { field: 'code', minWidth: 140, title: '编码' },
    {
      field: 'data_scope',
      formatter: ({ cellValue }) =>
        dataScopeOptions.find((o) => o.value === cellValue)?.label ??
        String(cellValue ?? ''),
      minWidth: 140,
      title: '数据范围',
    },
    { field: 'sort', title: '排序', width: 80 },
    {
      cellRender: {
        name: 'CellTag',
        options: [
          { label: '启用', type: 'success', value: 1 },
          { label: '禁用', type: 'info', value: 0 },
        ],
      },
      field: 'status',
      title: '状态',
      width: 90,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: (row: RoleRow) => !isSuperAdminRole(row),
            text: '编辑',
            type: 'primary',
          },
          { code: 'menus', text: '分配菜单', type: 'primary' },
          {
            code: 'delete',
            show: (row: RoleRow) => !isSuperAdminRole(row),
            text: '删除',
            type: 'danger',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 260,
    },
  ];
}
