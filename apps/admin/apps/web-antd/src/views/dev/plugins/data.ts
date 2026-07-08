import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { PluginRow } from '#/api/dev-plugin';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import {
  PLUGIN_PERMISSIONS,
  PLUGIN_STATUS,
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
          {
            code: 'config',
            show: (row: PluginRow) =>
              access.can(PLUGIN_PERMISSIONS.CONFIG) &&
              row.has_config &&
              row.installed,
            text: '配置',
            type: 'link',
          },
          {
            code: 'install',
            show: (row: PluginRow) =>
              access.can(PLUGIN_PERMISSIONS.INSTALL) &&
              row.status === PLUGIN_STATUS.DISCOVERED,
            text: '安装',
            type: 'link',
          },
          {
            code: 'uninstall',
            danger: true,
            show: (row: PluginRow) =>
              access.can(PLUGIN_PERMISSIONS.UNINSTALL) &&
              [PLUGIN_STATUS.INSTALLED, PLUGIN_STATUS.DISABLED].includes(
                row.status,
              ),
            text: '卸载',
            type: 'link',
          },
          {
            code: 'enable',
            show: (row: PluginRow) =>
              access.can(PLUGIN_PERMISSIONS.ENABLE) &&
              [PLUGIN_STATUS.INSTALLED, PLUGIN_STATUS.DISABLED].includes(
                row.status,
              ),
            text: '启用',
            type: 'link',
          },
          {
            code: 'disable',
            danger: true,
            show: (row: PluginRow) =>
              access.can(PLUGIN_PERMISSIONS.DISABLE) &&
              row.status === PLUGIN_STATUS.ENABLED,
            text: '禁用',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 260,
    },
  ];
}
