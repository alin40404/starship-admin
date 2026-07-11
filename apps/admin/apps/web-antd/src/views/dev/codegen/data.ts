import type { VbenFormSchema } from '#/adapter/form';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { GeneratorTableRow } from '#/api/generator';

import { formatDateTime } from '@vben/utils';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import { GENERATOR_PERMISSIONS } from '#/constants/generator-permissions';
import { $t } from '#/locales';

export type GeneratorAccessChecker = {
  can: (code: string) => boolean;
};

export function getGeneratorStatusTagOptions() {
  return [
    { color: 'default', label: $t('dev.codegen.statusDraft'), value: 'draft' },
    {
      color: 'success',
      label: $t('dev.codegen.statusGenerated'),
      value: 'generated',
    },
  ] as const;
}

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: {
        class: 'w-full',
        placeholder: $t('dev.codegen.keywordPlaceholder'),
      },
      fieldName: 'keyword',
      label: $t('dev.shared.keyword'),
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<GeneratorTableRow>,
  access: GeneratorAccessChecker,
) {
  return [
    {
      align: 'left',
      field: 'table_name',
      minWidth: 200,
      title: $t('dev.codegen.columnTable'),
    },
    {
      align: 'left',
      field: 'business_name',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: $t('dev.codegen.columnBusinessName'),
    },
    {
      align: 'left',
      field: 'module_slug',
      minWidth: 100,
      title: $t('dev.codegen.columnModule'),
    },
    {
      align: 'left',
      field: 'class_name',
      minWidth: 120,
      title: $t('dev.codegen.columnClassName'),
    },
    {
      align: 'center',
      field: 'columns_count',
      title: $t('dev.codegen.columnCount'),
      width: 80,
    },
    {
      align: 'center',
      cellRender: {
        name: 'CellTag',
        options: [...getGeneratorStatusTagOptions()],
      },
      field: 'status',
      title: $t('dev.shared.status'),
      width: 90,
    },
    {
      align: 'left',
      field: 'generated_at',
      formatter: ({ cellValue }) =>
        cellValue ? formatDateTime(cellValue as string) : TABLE_CELL_EMPTY,
      minWidth: 160,
      title: $t('dev.codegen.columnGeneratedAt'),
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'business_name',
          nameTitle: $t('dev.codegen.entity'),
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'preview',
            show: () => access.can(GENERATOR_PERMISSIONS.PREVIEW),
            text: $t('dev.shared.preview'),
            type: 'link',
          },
          {
            code: 'generate',
            show: () => access.can(GENERATOR_PERMISSIONS.GENERATE),
            text: $t('dev.shared.generate'),
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(GENERATOR_PERMISSIONS.DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 180,
    },
  ];
}
