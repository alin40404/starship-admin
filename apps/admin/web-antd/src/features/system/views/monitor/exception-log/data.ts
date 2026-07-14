import type { VbenFormSchema } from '#/adapter/form';
import type {
  CrudGridColumn,
  MobileCrudListConfig,
} from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { ExceptionLogRow } from '#/features/system/api/monitor';

import { formatDateTime } from '@vben/utils';

import {
  formatExceptionClassLabel,
  getExceptionTypeColor,
  MONITOR_EXCEPTION_LOG_PERMISSIONS,
} from '#/features/system/constants/monitor-permissions';
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
        placeholder: $t('monitor.exceptionLog.keywordPlaceholder'),
      },
      fieldName: 'keyword',
      label: $t('monitor.shared.keyword'),
    },
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('monitor.exceptionLog.exceptionClassPlaceholder'),
      },
      fieldName: 'exception_class',
      label: $t('monitor.shared.exceptionClass'),
    },
  ];
}

export function buildExceptionLogMobileCrudConfig(params: {
  columns: CrudGridColumn<ExceptionLogRow>[];
  query: MobileCrudListConfig<ExceptionLogRow>['query'];
}): MobileCrudListConfig<ExceptionLogRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 20,
    query: params.query,
    rowKey: 'id',
    search: {
      filterSchema: useGridFormSchema(),
      keywordFields: ['message', 'exception_class', 'trace_id', 'request_url'],
      placeholder: $t('monitor.exceptionLog.searchPlaceholder'),
    },
    selection: {
      enabled: true,
    },
  });
}

export function useColumns(
  onActionClick: OnActionClickFn<ExceptionLogRow>,
  access: MonitorAccessChecker,
): CrudGridColumn<ExceptionLogRow>[] {
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
      title: $t('monitor.exceptionLog.columnMessage'),
    },
    {
      align: 'left',
      cellRender: {
        attrs: {
          getColor: (row: ExceptionLogRow) =>
            getExceptionTypeColor(row.exception_class),
          getLabel: (row: ExceptionLogRow) =>
            formatExceptionClassLabel(row.exception_class) || TABLE_CELL_EMPTY,
        },
        name: 'CellTag',
      },
      field: 'exception_class',
      minWidth: 160,
      mobile: {
        format: (row) =>
          formatExceptionClassLabel(row.exception_class) || TABLE_CELL_EMPTY,
      },
      title: $t('monitor.exceptionLog.columnExceptionType'),
    },
    {
      align: 'left',
      field: 'request_url',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 180,
      title: $t('monitor.operationLog.requestUrl'),
    },
    {
      align: 'left',
      field: 'admin_name',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      width: 100,
      title: $t('monitor.shared.operator'),
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
      title: $t('monitor.exceptionLog.columnOccurredAt'),
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'message',
          nameTitle: $t('monitor.exceptionLog.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'detail',
            show: () => access.can(MONITOR_EXCEPTION_LOG_PERMISSIONS.QUERY),
            text: $t('monitor.shared.detail'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(MONITOR_EXCEPTION_LOG_PERMISSIONS.DELETE),
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
