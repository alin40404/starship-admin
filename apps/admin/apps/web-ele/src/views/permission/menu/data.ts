import type { VbenFormSchema } from '#/adapter/form';
import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { MenuNode } from '#/api/system/menu';

export function useColumns(
  onActionClick: OnActionClickFn<MenuNode>,
): VxeTableGridColumns {
  return [
    { field: 'name', minWidth: 200, title: '名称', treeNode: true },
    {
      field: 'type',
      formatter: ({ cellValue }) => {
        const t = Number(cellValue);
        if (t === 1) return '目录';
        if (t === 2) return '菜单';
        if (t === 3) return '按钮';
        return String(cellValue ?? '');
      },
      title: '类型',
      width: 90,
    },
    { field: 'path', minWidth: 160, title: '路由路径' },
    { field: 'permission', minWidth: 160, title: '权限标识' },
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
          { code: 'append', text: '子项', type: 'primary' },
          'edit',
          'delete',
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 260,
    },
  ];
}

export function buildMenuFormSchema(
  parentOptions: { label: string; value: number; disabled?: boolean }[],
): VbenFormSchema[] {
  return [
    {
      component: 'Select',
      componentProps: {
        filterable: true,
        options: [{ label: '（根）', value: 0 }, ...parentOptions],
        placeholder: '上级',
      },
      defaultValue: 0,
      fieldName: 'parent_id',
      label: '上级',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        options: [
          { label: '目录', value: 1 },
          { label: '菜单', value: 2 },
          { label: '按钮', value: 3 },
        ],
      },
      defaultValue: 2,
      fieldName: 'type',
      label: '类型',
    },
    {
      component: 'Input',
      fieldName: 'name',
      label: '名称',
      rules: 'required',
    },
    {
      component: 'Input',
      fieldName: 'path',
      label: '路径',
    },
    {
      component: 'Input',
      fieldName: 'component',
      label: '组件',
    },
    {
      component: 'Input',
      fieldName: 'permission',
      label: '权限标识',
    },
    {
      component: 'Input',
      fieldName: 'icon',
      label: '图标',
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
          { label: '显示', value: 1 },
          { label: '隐藏', value: 0 },
        ],
      },
      defaultValue: 1,
      fieldName: 'visible',
      label: '显示',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        options: [
          { label: '是', value: 1 },
          { label: '否', value: 0 },
        ],
      },
      defaultValue: 0,
      fieldName: 'keep_alive',
      label: '缓存',
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
