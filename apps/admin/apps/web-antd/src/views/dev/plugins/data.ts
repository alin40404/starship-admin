import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { PluginRow } from '#/api/dev-plugin';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import {
  PLUGIN_PERMISSIONS,
  PLUGIN_STATUS_TAG_OPTIONS,
} from '#/constants/plugin-permissions';

export type PluginAccessChecker = {
  can: (code: string) => boolean;
};

export function useColumns(
  onActionClick: OnActionClickFn<PluginRow>,
  access: PluginAccessChecker,
) {
  return [
    {
      align: 'left',
      field: 'slug',
      minWidth: 140,
      title: '插件标识',
    },
    {
      align: 'left',
      field: 'name',
      minWidth: 120,
      title: '插件名称',
    },
    {
      align: 'left',
      field: 'description',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 180,
      title: '描述',
    },
    {
      align: 'center',
      field: 'version',
      title: '版本',
      width: 100,
    },
    {
      align: 'left',
      field: 'type',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      title: '类型',
      width: 100,
    },
    {
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: [...PLUGIN_STATUS_TAG_OPTIONS],
      },
      field: 'status',
      title: '状态',
      width: 100,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: '插件',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'detail',
            show: () => access.can(PLUGIN_PERMISSIONS.QUERY),
            text: '详情',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 90,
    },
  ];
}
