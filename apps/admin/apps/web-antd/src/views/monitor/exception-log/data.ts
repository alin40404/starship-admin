import type { VbenFormSchema } from '#/adapter/form';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { ExceptionLogRow } from '#/api/monitor';

import { formatDateTime } from '@vben/utils';

import { MONITOR_EXCEPTION_LOG_PERMISSIONS } from '#/constants/monitor-permissions';
import { TABLE_CELL_EMPTY } from '#/constants/table';

export type MonitorAccessChecker = {
  can: (code: string) => boolean;
};

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '消息 / 异常类 / trace_id' },
      fieldName: 'keyword',
      label: '关键词',
    },
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: 'Exception 类名' },
      fieldName: 'exception_class',
      label: '异常类',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<ExceptionLogRow>,
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
      field: 'message',
      minWidth: 220,
      title: '异常消息',
    },
    {
      align: 'left',
      field: 'exception_class',
      formatter: ({ cellValue }) => {
        if (!cellValue) {
          return TABLE_CELL_EMPTY;
        }
        const text = String(cellValue);
        const parts = text.split('\\');
        return parts[parts.length - 1] || text;
      },
      minWidth: 160,
      title: '异常类型',
    },
    {
      align: 'left',
      field: 'request_url',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 180,
      title: '请求 URL',
    },
    {
      align: 'left',
      field: 'admin_name',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      width: 100,
      title: '操作人',
    },
    {
      align: 'left',
      field: 'trace_id',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 280,
      title: 'Trace ID',
    },
    {
      align: 'left',
      field: 'created_at',
      formatter: ({ cellValue }) =>
        cellValue ? formatDateTime(cellValue as string) : TABLE_CELL_EMPTY,
      minWidth: 160,
      title: '发生时间',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'message',
          nameTitle: '异常日志',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'detail',
            show: () => access.can(MONITOR_EXCEPTION_LOG_PERMISSIONS.QUERY),
            text: '详情',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(MONITOR_EXCEPTION_LOG_PERMISSIONS.DELETE),
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
