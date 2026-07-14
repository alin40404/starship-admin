import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { DevModuleRow } from '#/api/dev-module';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import {
  getModuleStatusTagOptions,
  MODULE_PERMISSIONS,
} from '#/constants/module-permissions';
import { $t } from '#/locales';

export type ModuleAccessChecker = {
  can: (code: string) => boolean;
};

export function useColumns(
  onActionClick: OnActionClickFn<DevModuleRow>,
  access: ModuleAccessChecker,
) {
  return [
    {
      align: 'left',
      field: 'name',
      minWidth: 120,
      title: $t('dev.modules.columnName'),
    },
    {
      align: 'left',
      field: 'title',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: $t('dev.modules.columnTitle'),
    },
    {
      align: 'center',
      field: 'version',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      title: $t('dev.shared.version'),
      width: 100,
    },
    {
      align: 'left',
      field: 'directory',
      minWidth: 120,
      title: $t('dev.shared.directory'),
    },
    {
      align: 'center',
      field: 'providers_count',
      title: 'Provider',
      width: 100,
    },
    {
      align: 'center',
      field: 'seeders_count',
      title: 'Seeder',
      width: 90,
    },
    {
      align: 'center',
      field: 'dev_only',
      slots: { default: 'devOnly' },
      title: $t('dev.modules.devOnlyColumn'),
      width: 120,
    },
    {
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: [...getModuleStatusTagOptions()],
      },
      field: 'enabled',
      title: $t('dev.shared.status'),
      width: 90,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'title',
          nameTitle: $t('dev.modules.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'detail',
            show: () => access.can(MODULE_PERMISSIONS.QUERY),
            text: $t('dev.shared.detail'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 90,
    },
  ];
}
