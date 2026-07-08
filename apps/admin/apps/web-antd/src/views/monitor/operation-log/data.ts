import type { VbenFormSchema } from '#/adapter/form';
import type {
  CrudGridColumn,
  MobileCrudListConfig,
} from '#/adapter/mobile-list';
import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { OperationLogRow } from '#/api/monitor';

import { formatDateTime } from '@vben/utils';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import {
  getOperationLogModuleColor,
  getOperationLogStatusCodeColor,
  MONITOR_HTTP_METHOD_TAG_OPTIONS,
  MONITOR_OPERATION_LOG_MODULE_OPTIONS,
  MONITOR_OPERATION_LOG_PERMISSIONS,
} from '#/constants/monitor-permissions';

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
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...MONITOR_OPERATION_LOG_MODULE_OPTIONS],
        placeholder: '请选择模块',
      },
      fieldName: 'module',
      label: '模块',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: [...MONITOR_HTTP_METHOD_TAG_OPTIONS],
        placeholder: 'POST / PUT',
      },
      fieldName: 'method',
      label: '方法',
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
      placeholder: '搜索标题/操作人/URL',
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
      title: '模块',
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
      title: '方法',
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
      title: '状态码',
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
