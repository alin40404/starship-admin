import type { VbenFormSchema } from '#/adapter/form';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { LoginLogRow } from '#/api/monitor';

import { formatDateTime } from '@vben/utils';

import {
  MONITOR_LOGIN_ACTION_TAG_OPTIONS,
  MONITOR_LOGIN_LOG_PERMISSIONS,
  MONITOR_LOGIN_STATUS_TAG_OPTIONS,
} from '#/constants/monitor-permissions';
import { TABLE_CELL_EMPTY } from '#/constants/table';

export type MonitorAccessChecker = {
  can: (code: string) => boolean;
};

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '账号 / IP' },
      fieldName: 'keyword',
      label: '关键词',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...MONITOR_LOGIN_ACTION_TAG_OPTIONS],
        placeholder: '动作',
      },
      fieldName: 'action',
      label: '动作',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...MONITOR_LOGIN_STATUS_TAG_OPTIONS],
        placeholder: '结果',
      },
      fieldName: 'status',
      label: '结果',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<LoginLogRow>,
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
      field: 'username',
      minWidth: 120,
      title: '账号',
    },
    {
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: [...MONITOR_LOGIN_ACTION_TAG_OPTIONS],
      },
      field: 'action',
      title: '动作',
      width: 90,
    },
    {
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: [...MONITOR_LOGIN_STATUS_TAG_OPTIONS],
      },
      field: 'status',
      title: '结果',
      width: 90,
    },
    {
      align: 'left',
      field: 'message',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 140,
      title: '说明',
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
      title: '时间',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'username',
          nameTitle: '登录日志',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'detail',
            show: () => access.can(MONITOR_LOGIN_LOG_PERMISSIONS.QUERY),
            text: '详情',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(MONITOR_LOGIN_LOG_PERMISSIONS.DELETE),
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
