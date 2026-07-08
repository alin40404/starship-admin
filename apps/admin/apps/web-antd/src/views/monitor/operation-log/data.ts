import type { VbenFormSchema } from '#/adapter/form';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { OperationLogRow } from '#/api/monitor';

import { formatDateTime } from '@vben/utils';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import { MONITOR_OPERATION_LOG_PERMISSIONS } from '#/constants/monitor-permissions';

export type MonitorAccessChecker = {
  can: (code: string) => boolean;
};

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '标题 / 操作人 / URL' },
      fieldName: 'keyword',
      label: '关键词',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: 'system / generator' },
      fieldName: 'module',
      label: '模块',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: 'POST / PUT' },
      fieldName: 'method',
      label: '方法',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<OperationLogRow>,
  access: MonitorAccessChecker,
) {
  return [
    {
      align: 'center',
      fixed: 'left',
      type: 'checkbox',
      width: 48,
    },
    {
      align: 'left',
      field: 'title',
      minWidth: 180,
      title: '操作标题',
    },
    {
      align: 'left',
      field: 'admin_name',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 100,
      title: '操作人',
    },
    {
      align: 'left',
      field: 'module',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      width: 100,
      title: '模块',
    },
    {
      align: 'center',
      field: 'method',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      width: 80,
      title: '方法',
    },
    {
      align: 'center',
      field: 'status_code',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      width: 90,
      title: '状态码',
    },
    {
      align: 'left',
      field: 'ip',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      width: 130,
      title: 'IP',
    },
    {
      align: 'left',
      field: 'created_at',
      formatter: ({ cellValue }) =>
        cellValue ? formatDateTime(cellValue as string) : TABLE_CELL_EMPTY,
      minWidth: 160,
      title: '操作时间',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'title',
          nameTitle: '操作日志',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'detail',
            show: () => access.can(MONITOR_OPERATION_LOG_PERMISSIONS.QUERY),
            text: '详情',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(MONITOR_OPERATION_LOG_PERMISSIONS.DELETE),
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
