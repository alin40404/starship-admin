import type {
  CrudGridColumn,
  MobileCrudListConfig,
} from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';
import type { PluginRow } from '#/api/dev-plugin';

import { buildMobileCrudFromColumns } from '#/adapter/mobile-list';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import {
  getPluginStatusTagOptions,
  PLUGIN_PERMISSIONS,
  PLUGIN_STATUS,
} from '#/constants/plugin-permissions';
import { $t } from '#/locales';

export type PluginAccessChecker = {
  can: (code: string) => boolean;
};

export function buildPluginMobileCrudConfig(params: {
  columns: CrudGridColumn<PluginRow>[];
  query: MobileCrudListConfig<PluginRow>['query'];
}): MobileCrudListConfig<PluginRow> {
  return buildMobileCrudFromColumns({
    columns: params.columns,
    defaultPageSize: 50,
    query: params.query,
    rowKey: 'slug',
    search: {
      keywordFields: ['slug', 'name', 'description'],
      placeholder: $t('dev.plugins.searchPlaceholder'),
      submitOnChange: true,
    },
    selection: {
      enabled: true,
    },
  });
}

export function useColumns(
  onActionClick: OnActionClickFn<PluginRow>,
  access: PluginAccessChecker,
): CrudGridColumn<PluginRow>[] {
  return [
    { align: 'center', fixed: 'left', type: 'checkbox', width: 48 },
    {
      align: 'left',
      field: 'slug',
      minWidth: 140,
      title: $t('dev.plugins.columnSlug'),
    },
    {
      align: 'left',
      field: 'name',
      minWidth: 120,
      title: $t('dev.plugins.columnName'),
    },
    {
      align: 'left',
      field: 'description',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 180,
      title: $t('dev.shared.description'),
    },
    {
      align: 'center',
      field: 'version',
      title: $t('dev.shared.version'),
      width: 100,
    },
    {
      align: 'left',
      field: 'type',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      title: $t('dev.shared.type'),
      width: 100,
    },
    {
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: [...getPluginStatusTagOptions()],
      },
      field: 'status',
      mobile: {
        format: (row) => row.status_label || TABLE_CELL_EMPTY,
      },
      title: $t('dev.shared.status'),
      width: 100,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: $t('dev.plugins.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'detail',
            show: () => access.can(PLUGIN_PERMISSIONS.QUERY),
            text: $t('dev.shared.detail'),
            type: 'link',
          },
          {
            code: 'config',
            show: (row: PluginRow) =>
              access.can(PLUGIN_PERMISSIONS.CONFIG) &&
              row.has_config &&
              row.installed,
            text: $t('dev.shared.config'),
            type: 'link',
          },
          {
            code: 'install',
            show: (row: PluginRow) =>
              access.can(PLUGIN_PERMISSIONS.INSTALL) &&
              row.status === PLUGIN_STATUS.DISCOVERED,
            text: $t('dev.shared.install'),
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
            text: $t('dev.shared.uninstall'),
            type: 'link',
          },
          {
            code: 'enable',
            show: (row: PluginRow) =>
              access.can(PLUGIN_PERMISSIONS.ENABLE) &&
              [PLUGIN_STATUS.INSTALLED, PLUGIN_STATUS.DISABLED].includes(
                row.status,
              ),
            text: $t('dev.shared.enable'),
            type: 'link',
          },
          {
            code: 'disable',
            danger: true,
            show: (row: PluginRow) =>
              access.can(PLUGIN_PERMISSIONS.DISABLE) &&
              row.status === PLUGIN_STATUS.ENABLED,
            text: $t('dev.shared.disable'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 260,
    },
  ];
}
