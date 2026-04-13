import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { RoleRow } from '#/api/system/role';

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
  ];
}

export function buildDataScopeFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        disabled: true,
      },
      fieldName: 'name',
      label: '角色',
    },
    {
      component: 'Select',
      componentProps: {
        options: dataScopeOptions,
      },
      fieldName: 'data_scope',
      label: '数据范围',
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
    { field: 'name', minWidth: 140, title: '角色名称' },
    { field: 'code', minWidth: 140, title: '编码' },
    {
      field: 'data_scope',
      formatter: ({ cellValue }) =>
        dataScopeOptions.find((o) => o.value === cellValue)?.label ??
        String(cellValue ?? ''),
      minWidth: 160,
      title: '数据范围',
    },
    { field: 'remark', minWidth: 160, showOverflow: true, title: '备注' },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [{ code: 'edit', text: '配置范围', type: 'primary' }],
      },
      fixed: 'right',
      title: '操作',
      width: 120,
    },
  ];
}
