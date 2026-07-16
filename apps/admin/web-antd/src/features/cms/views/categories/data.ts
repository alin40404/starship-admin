import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { CategoryRow } from '#/features/cms/api';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { CMS_PERMISSIONS } from '#/features/cms/constants/cms-permissions';
import { $t } from '#/locales';
import { formatDateTime } from '@vben/utils';

export type CmsAccessChecker = {
  can: (code: string) => boolean;
};

function getStatusOptions() {
  return [
    { label: $t('system.shared.status.enable'), value: 1 },
    { label: $t('system.shared.status.disable'), value: 0 },
  ];
}

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '名称/标识' },
      fieldName: 'keyword',
      label: '关键词',
    },
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        class: 'w-full',
        options: getStatusOptions(),
      },
      fieldName: 'status',
      label: $t('org.shared.status'),
    },
  ];
}

export function buildCategoryFormSchema(): VbenFormSchema[] {
  return [
    { component: 'Input', fieldName: 'name', label: '栏目名称', rules: 'required' },
    { component: 'Input', fieldName: 'slug', label: '标识 slug', rules: 'required' },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'parent_id',
      label: '父级 ID',
    },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: '排序',
    },
    {
      component: 'RadioGroup',
      componentProps: { options: getStatusOptions() },
      defaultValue: 1,
      fieldName: 'status',
      label: $t('org.shared.status'),
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<CategoryRow>,
  access: CmsAccessChecker,
  onStatusBeforeChange: (newVal: number, row: CategoryRow) => Promise<boolean>,
): CrudGridColumn<CategoryRow>[] {
  return [
    { field: 'id', title: 'ID', width: 72 },
    { field: 'name', minWidth: 140, title: '栏目名称' },
    { field: 'slug', minWidth: 140, title: '标识' },
    { field: 'parent_id', title: '父级', width: 80 },
    { field: 'sort', title: '排序', width: 80 },
    {
      field: 'status',
      title: $t('org.shared.status'),
      width: 100,
      cellRender: {
        attrs: {
          async beforeChange(newVal: unknown, row: CategoryRow) {
            return onStatusBeforeChange(newVal as number, row);
          },
          getSwitchProps: () => ({
            disabled: !access.can(CMS_PERMISSIONS.CATEGORIES_STATUS),
          }),
        },
        name: 'CellSwitch',
      },
    },
    {
      field: 'created_at',
      formatter: ({ row }: { row: CategoryRow }) =>
        row.created_at ? formatDateTime(row.created_at) : TABLE_CELL_EMPTY,
      minWidth: 170,
      title: '创建时间',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: '栏目',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(CMS_PERMISSIONS.CATEGORIES_UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            show: () => access.can(CMS_PERMISSIONS.CATEGORIES_DELETE),
            text: $t('common.delete'),
            type: 'link',
          },
        ],
      },
      field: 'operation',
      fixed: 'right',
      title: $t('crud.columnActions'),
      width: 140,
    },
  ];
}
