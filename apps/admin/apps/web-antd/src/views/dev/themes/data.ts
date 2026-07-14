import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { ThemeRow } from '#/api/dev-theme';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import {
  getThemeStatusTagOptions,
  THEME_PERMISSIONS,
  THEME_STATUS,
} from '#/constants/theme-permissions';
import { $t } from '#/locales';

export type ThemeAccessChecker = {
  can: (code: string) => boolean;
};

export function useColumns(
  onActionClick: OnActionClickFn<ThemeRow>,
  access: ThemeAccessChecker,
) {
  return [
    { align: 'center', fixed: 'left', type: 'checkbox', width: 48 },
    {
      align: 'left',
      field: 'slug',
      minWidth: 120,
      title: $t('dev.themes.columnSlug'),
    },
    {
      align: 'left',
      field: 'title',
      minWidth: 120,
      title: $t('dev.themes.columnTitle'),
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
        options: [...getThemeStatusTagOptions()],
      },
      field: 'status',
      title: $t('dev.shared.status'),
      width: 100,
    },
    {
      align: 'center',
      field: 'is_active',
      formatter: ({ cellValue }) =>
        cellValue ? $t('dev.themes.activeYes') : TABLE_CELL_EMPTY,
      title: $t('dev.themes.columnActive'),
      width: 90,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'title',
          nameTitle: $t('dev.themes.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'detail',
            show: () => access.can(THEME_PERMISSIONS.QUERY),
            text: $t('dev.shared.detail'),
            type: 'link',
          },
          {
            code: 'install',
            show: (row: ThemeRow) =>
              access.can(THEME_PERMISSIONS.INSTALL) &&
              row.status === THEME_STATUS.DISCOVERED,
            text: $t('dev.shared.install'),
            type: 'link',
          },
          {
            code: 'uninstall',
            danger: true,
            show: (row: ThemeRow) =>
              access.can(THEME_PERMISSIONS.UNINSTALL) &&
              [THEME_STATUS.INSTALLED, THEME_STATUS.DISABLED].includes(
                row.status,
              ),
            text: $t('dev.shared.uninstall'),
            type: 'link',
          },
          {
            code: 'enable',
            show: (row: ThemeRow) =>
              access.can(THEME_PERMISSIONS.ENABLE) &&
              [THEME_STATUS.INSTALLED, THEME_STATUS.DISABLED].includes(
                row.status,
              ),
            text: $t('dev.shared.enable'),
            type: 'link',
          },
          {
            code: 'disable',
            danger: true,
            show: (row: ThemeRow) =>
              access.can(THEME_PERMISSIONS.DISABLE) &&
              row.status === THEME_STATUS.ENABLED,
            text: $t('dev.shared.disable'),
            type: 'link',
          },
          {
            code: 'switch',
            show: (row: ThemeRow) =>
              access.can(THEME_PERMISSIONS.SWITCH) &&
              row.status === THEME_STATUS.ENABLED &&
              !row.is_active,
            text: $t('dev.themes.switch'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 300,
    },
  ];
}
