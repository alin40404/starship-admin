import type { VbenFormSchema } from '#/adapter/form';
import type { CrudGridColumn } from '#/adapter/mobile-list';
import type { OnActionClickFn } from '#/adapter/vxe-table';

import type { BrandRow } from '#/features/shop/api';
import { TABLE_CELL_EMPTY } from '#/constants/table';
import { SHOP_PERMISSIONS } from '#/features/shop/constants/shop-permissions';
import { $t } from '#/locales';
import { formatDateTime } from '@vben/utils';

export type ShopAccessChecker = {
  can: (code: string) => boolean;
};

export function useGridFormSchema(): VbenFormSchema[] {
  return [
    {
      component: 'Input',
      componentProps: { class: 'w-full', placeholder: '品牌名称' },
      fieldName: 'keyword',
      label: '关键词',
    },
  ];
}

export function buildBrandFormSchema(): VbenFormSchema[] {
  return [
    { component: 'Input', fieldName: 'name', label: '品牌名称', rules: 'required' },
    { component: 'Input', fieldName: 'logo', label: 'Logo URL' },
    {
      component: 'InputNumber',
      componentProps: { class: 'w-full', min: 0 },
      defaultValue: 0,
      fieldName: 'sort',
      label: '排序',
    },
    {
      component: 'RadioGroup',
      componentProps: {
        options: [
          { label: '启用', value: 1 },
          { label: '禁用', value: 0 },
        ],
      },
      defaultValue: 1,
      fieldName: 'status',
      label: '状态',
    },
  ];
}

export function useColumns(
  onActionClick: OnActionClickFn<BrandRow>,
  access: ShopAccessChecker,
): CrudGridColumn<BrandRow>[] {
  return [
    { field: 'id', title: 'ID', width: 72 },
    { field: 'name', minWidth: 160, title: '品牌名称' },
    { field: 'logo', minWidth: 180, title: 'Logo' },
    { field: 'sort', title: '排序', width: 90 },
    {
      field: 'status',
      formatter: ({ cellValue }) => (Number(cellValue) === 1 ? '启用' : '禁用'),
      title: '状态',
      width: 90,
    },
    {
      field: 'created_at',
      formatter: ({ row }: { row: BrandRow }) =>
        row.created_at ? formatDateTime(row.created_at) : TABLE_CELL_EMPTY,
      minWidth: 170,
      title: '创建时间',
    },
    {
      align: 'center',
      cellRender: {
        attrs: {
          nameField: 'name',
          nameTitle: '品牌',
          onClick: onActionClick,
        },
        name: 'CellOperation',
        options: [
          {
            code: 'edit',
            show: () => access.can(SHOP_PERMISSIONS.BRANDS_UPDATE),
            text: $t('common.edit'),
            type: 'link',
          },
          {
            code: 'delete',
            show: () => access.can(SHOP_PERMISSIONS.BRANDS_DELETE),
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
