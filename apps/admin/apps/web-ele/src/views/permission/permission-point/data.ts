import type {
  OnActionClickFn,
  VxeTableGridColumns,
} from '#/adapter/vxe-table';

import type { MenuNode } from '#/api/system/menu';

export function useColumns(
  onActionClick: OnActionClickFn<MenuNode>,
): VxeTableGridColumns {
  return [
    { field: 'id', title: 'ID', width: 70 },
    { field: 'name', minWidth: 140, title: '名称' },
    {
      field: '_parentName',
      formatter: ({ row }) => (row as MenuNode & { _parentName?: string })._parentName ?? '—',
      minWidth: 160,
      title: '所属菜单',
    },
    { field: 'permission', minWidth: 180, title: '权限标识' },
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
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [{ code: 'hint', text: '说明', type: 'primary' }],
      },
      fixed: 'right',
      title: '操作',
      width: 100,
    },
  ];
}
