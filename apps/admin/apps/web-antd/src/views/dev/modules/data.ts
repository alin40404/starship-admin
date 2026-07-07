import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { DevModuleRow } from '#/api/dev-module';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import { MODULE_STATUS_TAG_OPTIONS } from '#/constants/module-permissions';

export type ModuleAccessChecker = {
  can: (code: string) => boolean;
};

export function useColumns(
  onActionClick: OnActionClickFn<DevModuleRow>,
) {
  return [
    {
      align: 'left',
      field: 'name',
      minWidth: 120,
      title: '模块标识',
    },
    {
      align: 'left',
      field: 'title',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: '模块名称',
    },
    {
      align: 'center',
      field: 'version',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      title: '版本',
      width: 100,
    },
    {
      align: 'left',
      field: 'directory',
      minWidth: 120,
      title: '目录',
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
      cellRender: {
        name: 'CellTag',
        options: [...MODULE_STATUS_TAG_OPTIONS],
      },
      field: 'enabled',
      title: '状态',
      width: 90,
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'title',
          nameTitle: '模块',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'detail',
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
