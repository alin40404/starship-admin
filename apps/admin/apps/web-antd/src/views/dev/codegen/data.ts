import type { VbenFormSchema } from '#/adapter/form';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { GeneratorTableRow } from '#/api/generator';

import { formatDateTime } from '@vben/utils';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import { GENERATOR_PERMISSIONS } from '#/constants/generator-permissions';

export type GeneratorAccessChecker = {
  can: (code: string) => boolean;
};

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '表名 / 业务名' },
      fieldName: 'keyword',
      label: '关键词',
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
      title: '数据表',
    },
    {
      align: 'left',
      field: 'business_name',
      formatter: ({ cellValue }) => cellValue ?? TABLE_CELL_EMPTY,
      minWidth: 120,
      title: '业务名称',
    },
    {
      align: 'left',
      field: 'module_slug',
      minWidth: 100,
      title: '模块',
    },
    {
      align: 'left',
      field: 'class_name',
      minWidth: 120,
      title: '类名',
    },
    {
      align: 'center',
      field: 'columns_count',
      title: '字段数',
      width: 80,
    },
    {
      align: 'center',
      field: 'status',
      formatter: ({ cellValue }) => (cellValue === 'generated' ? '已生成' : '草稿'),
      title: '状态',
      width: 90,
    },
    {
      align: 'left',
      field: 'generated_at',
      formatter: ({ cellValue }) =>
        cellValue ? formatDateTime(cellValue as string) : TABLE_CELL_EMPTY,
      minWidth: 160,
      title: '最后生成',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'business_name',
          nameTitle: '代码生成配置',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'preview',
            show: () => access.can(GENERATOR_PERMISSIONS.PREVIEW),
            text: '预览',
            type: 'link',
          },
          {
            code: 'generate',
            show: () => access.can(GENERATOR_PERMISSIONS.GENERATE),
            text: '生成',
            type: 'link',
          },
          {
            code: 'delete',
            danger: true,
            show: () => access.can(GENERATOR_PERMISSIONS.DELETE),
            text: '删除',
            type: 'link',
          },
        ],
      },
      fixed: 'right',
      title: '操作',
      width: 180,
    },
  ];
}
