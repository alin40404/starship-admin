import type { VbenFormSchema } from '#/adapter/form';
import type {
  CrudGridColumn,
  MobileCrudListConfig,
} from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { LoginLogRow } from '#/api/monitor';

import { formatDateTime } from '@vben/utils';

import {
  getMonitorLoginActionTagOptions,
  getMonitorLoginStatusTagOptions,
  MONITOR_LOGIN_LOG_PERMISSIONS,
} from '#/constants/monitor-permissions';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { $t } from '#/locales';

export type MonitorAccessChecker = {
  can: (code: string) => boolean;
};

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('system.monitor.loginLog.keywordPlaceholder'),
      },
      fieldName: 'keyword',
      label: $t('system.monitor.shared.keyword'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...getMonitorLoginActionTagOptions()],
        placeholder: $t('system.monitor.loginLog.actionPlaceholder'),
      },
      fieldName: 'action',
      label: $t('system.monitor.shared.action'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...getMonitorLoginStatusTagOptions()],
        placeholder: $t('system.monitor.loginLog.statusPlaceholder'),
      },
      fieldName: 'status',
      label: $t('system.monitor.shared.status'),
    },
  ];
}

export function buildLoginLogMobileCrudConfig(params: {
  columns: CrudGridColumn<LoginLogRow>[];
  query: MobileCrudListConfig<LoginLogRow>['query'];
}): MobileCrudListConfig<LoginLogRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 20,
    query: params.query,
    rowKey: 'id',
    search: {
      filterSchema: useGridFormSchema(),
      keywordFields: ['username', 'message', 'ip'],
      placeholder: $t('system.monitor.loginLog.searchPlaceholder'),
    },
    selection: {
      enabled: true,
    },
  });
}

export function useColumns(
  onActionClick: OnActionClickFn<LoginLogRow>,
  access: MonitorAccessChecker,
): CrudGridColumn<LoginLogRow>[] {
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
      title: $t('system.monitor.loginLog.columnUsername'),
    },
    {
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: [...getMonitorLoginActionTagOptions()],
      },
      field: 'action',
      mobile: {
        format: (row) =>
          row.action === 'logout'
            ? $t('system.monitor.shared.actionLogout')
            : $t('system.monitor.shared.actionLogin'),
      },
      title: $t('system.monitor.shared.action'),
      width: 90,
    },
    {
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: [...getMonitorLoginStatusTagOptions()],
      },
      field: 'status',
      mobile: {
        format: (row) =>
          row.status === 1
            ? $t('system.monitor.shared.statusSuccess')
            : $t('system.monitor.shared.statusFailed'),
      },
      title: $t('system.monitor.shared.status'),
      width: 90,
    },
    {
      align: 'left',
      field: 'message',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 140,
      title: $t('system.monitor.shared.message'),
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
      title: $t('system.monitor.shared.time'),
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'username',
          nameTitle: $t('system.monitor.loginLog.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'detail',
            show: () => access.can(MONITOR_LOGIN_LOG_PERMISSIONS.QUERY),
            text: $t('system.monitor.shared.detail'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(MONITOR_LOGIN_LOG_PERMISSIONS.DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 140,
    },
  ];
}
