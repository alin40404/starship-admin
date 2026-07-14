import type { VbenFormSchema } from '#/adapter/form';
import type {
  CrudGridColumn,
  MobileCrudListConfig,
} from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { OperationLogRow } from '#/features/system/api/monitor';

import { formatDateTime } from '@vben/utils';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import {
  getOperationLogModuleColor,
  getOperationLogStatusCodeColor,
  MONITOR_HTTP_METHOD_TAG_OPTIONS,
  MONITOR_OPERATION_LOG_MODULE_OPTIONS,
  MONITOR_OPERATION_LOG_PERMISSIONS,
} from '#/features/system/constants/monitor-permissions';
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
        placeholder: $t('monitor.operationLog.keywordPlaceholder'),
      },
      fieldName: 'keyword',
      label: $t('monitor.shared.keyword'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...MONITOR_OPERATION_LOG_MODULE_OPTIONS],
        placeholder: $t('monitor.operationLog.modulePlaceholder'),
      },
      fieldName: 'module',
      label: $t('monitor.shared.module'),
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...MONITOR_HTTP_METHOD_TAG_OPTIONS],
        placeholder: $t('monitor.operationLog.methodPlaceholder'),
      },
      fieldName: 'method',
      label: $t('monitor.shared.method'),
    },
  ];
}

export function buildOperationLogMobileCrudConfig(params: {
  columns: CrudGridColumn<OperationLogRow>[];
  query: MobileCrudListConfig<OperationLogRow>['query'];
}): MobileCrudListConfig<OperationLogRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 20,
    query: params.query,
    rowKey: 'id',
    search: {
      filterSchema: useGridFormSchema(),
      keywordFields: ['title', 'admin_name', 'url', 'action'],
      placeholder: $t('monitor.operationLog.searchPlaceholder'),
    },
    selection: {
      enabled: true,
    },
  });
}

export function useColumns(
  onActionClick: OnActionClickFn<OperationLogRow>,
  access: MonitorAccessChecker,
): CrudGridColumn<OperationLogRow>[] {
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
      title: $t('monitor.operationLog.columnTitle'),
    },
    {
      align: 'left',
      field: 'admin_name',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 100,
      title: $t('monitor.shared.operator'),
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          getColor: (row: OperationLogRow) =>
            getOperationLogModuleColor(row.module),
        },
        name: 'CellTag',
      },
      field: 'module',
      mobile: {
        format: (row) => row.module ?? TABLE_CELL_EMPTY,
      },
      title: $t('monitor.operationLog.columnModule'),
      width: 100,
    },
    {
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: [...MONITOR_HTTP_METHOD_TAG_OPTIONS],
      },
      field: 'method',
      mobile: {
        format: (row) => row.method ?? TABLE_CELL_EMPTY,
      },
      title: $t('monitor.operationLog.columnMethod'),
      width: 80,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          getColor: (row: OperationLogRow) =>
            getOperationLogStatusCodeColor(row.status_code),
        },
        name: 'CellTag',
      },
      field: 'status_code',
      mobile: {
        format: (row) =>
          row.status_code == null ? TABLE_CELL_EMPTY : String(row.status_code),
      },
      title: $t('monitor.operationLog.columnStatusCode'),
      width: 90,
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
      title: $t('monitor.operationLog.columnTime'),
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'title',
          nameTitle: $t('monitor.operationLog.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'detail',
            show: () => access.can(MONITOR_OPERATION_LOG_PERMISSIONS.QUERY),
            text: $t('monitor.shared.detail'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(MONITOR_OPERATION_LOG_PERMISSIONS.DELETE),
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
