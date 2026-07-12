import type { VbenFormSchema } from '#/adapter/form';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { GeneratorTableRow } from '#/api/generator';

import { capitalizeFirstLetter, formatDateTime } from '@vben/utils';

import { TABLE_CELL_EMPTY } from '#/constants/table';
import { GENERATOR_PERMISSIONS } from '#/constants/generator-permissions';
import { $t } from '#/locales';

import {
  defaultMenuParentPath,
  menuParentPathExists,
  type MenuParentTreeOption,
} from './menu-parent';

export type GeneratorAccessChecker = {
  can: (code: string) => boolean;
};

/** 与后端 Str::studly 一致，用于模块 slug 展示（如 system → System） */
export function formatModuleSlug(slug: string): string {
  return slug
    .split(/[-_]+/)
    .filter(Boolean)
    .map((part) => capitalizeFirstLetter(part.toLowerCase()))
    .join('');
}

export function toModuleSelectOption(slug: string) {
  const value = formatModuleSlug(slug);
  return { label: value, value };
}

export interface BuildImportFormSchemaParams {
  getMenuParentTree: () => MenuParentTreeOption[];
  loadingMenuParents: boolean;
  loadingTables: boolean;
  moduleOptions: Array<{ label: string; value: string }>;
  onTableSearch: (keyword: string) => void;
  tableOptions: Array<{ label: string; value: string }>;
}

export function buildImportFormSchema(
  params: BuildImportFormSchemaParams,
): VbenFormSchema[] {
  return [
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        loading: params.loadingTables,
        options: params.tableOptions,
        placeholder: $t('dev.codegen.tableSearchPlaceholder'),
        showSearch: true,
        onSearch: params.onTableSearch,
      },
      fieldName: 'table_name',
      label: $t('dev.codegen.physicalTable'),
      rules: 'selectRequired',
    },
    {
      component: 'Select',
      componentProps: {
        class: 'w-full',
        options: params.moduleOptions,
        placeholder: $t('dev.codegen.selectModule'),
      },
      fieldName: 'module',
      label: $t('dev.codegen.targetModule'),
      rules: 'selectRequired',
    },
    {
      component: 'ApiTreeSelect',
      componentProps: {
        allowClear: true,
        api: async () => params.getMenuParentTree(),
        class: 'w-full',
        loading: params.loadingMenuParents,
        placeholder: $t('dev.codegen.menuParentPlaceholder'),
        showSearch: true,
        treeDefaultExpandAll: true,
        treeNodeFilterProp: 'label',
      },
      dependencies: {
        triggerFields: ['module'],
        trigger(values, formApi) {
          const module = values.module as string | undefined;
          if (!module) {
            return;
          }
          const suggested = defaultMenuParentPath(module);
          const tree = params.getMenuParentTree();
          if (suggested && menuParentPathExists(tree, suggested)) {
            formApi.setFieldValue('menu_parent_path', suggested);
          } else {
            formApi.setFieldValue('menu_parent_path', undefined);
          }
        },
      },
      fieldName: 'menu_parent_path',
      label: $t('dev.codegen.menuParent'),
    },
    {
      component: 'Input',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        placeholder: $t('dev.codegen.businessPlaceholder'),
      },
      fieldName: 'business',
      label: $t('dev.codegen.businessName'),
    },
  ];
}

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
      formatter: ({ cellValue }) =>
        cellValue ? formatModuleSlug(String(cellValue)) : TABLE_CELL_EMPTY,
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
